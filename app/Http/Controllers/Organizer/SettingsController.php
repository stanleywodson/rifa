<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Payments\Contracts\ConnectsAccounts;
use App\Domain\Payments\Contracts\CreatesSubaccounts;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Models\KycDocument;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tenants\TenantContext;
use App\Http\Controllers\Controller;
use App\Rules\CpfCnpj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class SettingsController extends Controller
{
  public function __construct(protected TenantContext $context, protected AuditLogger $audit) {}

  public function edit(Request $request)
  {
    $tenant = $this->context->get()->load('plan', 'kycDocuments');

    return view('content.organizer.settings', [
      'tenant' => $tenant,
      'isOwner' => $request->user()->isOwnerOf($tenant),
      'documentTypes' => KycDocument::TYPES,
      'tab' => $request->query('aba', 'organizacao'),
      'gateways' => app(PaymentGatewayManager::class)->available(),
      'connectedGateway' => $tenant->paymentGateway(),
    ]);
  }

  public function update(Request $request)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);

    $data = $request->validate([
      'name' => ['required', 'string', 'max:120'],
      'email' => ['required', 'email', 'max:150'],
      'phone' => ['required', 'string', 'max:20'],
      // documento travado após a verificação
      'document' => [Rule::requiredIf(!$tenant->isVerified()), 'nullable', new CpfCnpj],
      'about' => ['nullable', 'string', 'max:1000'],
      'instagram' => ['nullable', 'string', 'max:80'],
    ], [], ['name' => 'nome', 'phone' => 'telefone', 'document' => 'CPF/CNPJ', 'about' => 'apresentação']);

    $original = $tenant->getAttributes();
    $tenant->fill([
      'name' => $data['name'],
      'email' => $data['email'],
      'phone' => only_digits($data['phone']),
      'settings' => array_merge($tenant->settings ?? [], ['about' => $data['about'] ?? null, 'instagram' => $data['instagram'] ?? null]),
    ]);
    if (!$tenant->isVerified() && isset($data['document'])) {
      $tenant->document = only_digits($data['document']);
    }
    $tenant->save();
    $this->audit->logChanges('tenant.updated', $tenant, $original);

    return back()->with('success', 'Dados da organização atualizados.');
  }

  public function uploadDocument(Request $request)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);

    $data = $request->validate([
      'type' => ['required', Rule::in(array_keys(KycDocument::TYPES))],
      'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
    ], [], ['type' => 'tipo de documento', 'file' => 'arquivo']);

    $document = $tenant->kycDocuments()->create([
      'uploaded_by' => $request->user()->id,
      'type' => $data['type'],
      'path' => $request->file('file')->store("tenants/{$tenant->id}/kyc", 'local'),
      'original_name' => mb_substr($request->file('file')->getClientOriginalName(), 0, 255),
    ]);

    if (in_array($tenant->kyc_status, [KycStatus::Pending, KycStatus::Rejected], true)) {
      $tenant->forceFill(['kyc_status' => KycStatus::Submitted])->save();
    }
    $this->audit->log('tenant.kyc_document', $document, null, ['type' => $document->type]);

    return redirect()->route('painel.configuracoes.edit', ['aba' => 'verificacao'])->with('success', 'Documento enviado para análise.');
  }

  /**
   * Cria a subconta de recebimento (split) do organizador no gateway (Asaas/simulado).
   */
  public function payments(Request $request, PaymentGatewayManager $gateways)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);

    $name = $request->input('gateway', config('saas.payment_gateway'));
    abort_unless(is_string($name) && $gateways->isEnabled($name), 404);
    $gateway = $gateways->driver($name);
    abort_unless($gateway instanceof CreatesSubaccounts, 404);

    if ($gateway->isConnected($tenant)) {
      return back()->with('info', 'A conta de recebimento já está configurada.');
    }
    if (blank($tenant->document)) {
      return redirect()->route('painel.configuracoes.edit')->with('error', 'Informe o CPF/CNPJ da organização antes de configurar os recebimentos.');
    }

    $data = $request->validate([
      'email' => ['required', 'email'],
      'phone' => ['required', 'string'],
      'birth_date' => [Rule::requiredIf(strlen((string) $tenant->document) === 11), 'nullable', 'date'],
      'company_type' => [Rule::requiredIf(strlen((string) $tenant->document) === 14), 'nullable', Rule::in(['MEI', 'LIMITED', 'INDIVIDUAL', 'ASSOCIATION'])],
      'income_value' => ['required', 'numeric', 'min:0'],
      'postal_code' => ['required', 'string', 'max:9'],
      'address' => ['required', 'string', 'max:150'],
      'address_number' => ['required', 'string', 'max:10'],
      'province' => ['required', 'string', 'max:80'],
    ], [], [
      'phone' => 'celular', 'birth_date' => 'data de nascimento', 'company_type' => 'tipo de empresa',
      'income_value' => 'faturamento mensal', 'postal_code' => 'CEP', 'address' => 'endereço',
      'address_number' => 'número', 'province' => 'bairro',
    ]);

    if (!$this->canSwitchGateway($tenant)) {
      return $this->switchBlocked();
    }

    try {
      $account = $gateway->createSubaccount($tenant, $data);
    } catch (Throwable $e) {
      Log::error('Falha ao criar subconta', ['tenant' => $tenant->id, 'gateway' => $name, 'error' => $e->getMessage()]);

      return back()->withInput()->with('error', 'Não foi possível criar a conta de recebimento: ' . $e->getMessage());
    }

    $tenant->forceFill([
      'asaas_account_id' => $account['account_id'],
      'asaas_wallet_id' => $account['wallet_id'],
      'asaas_api_key' => $account['api_key'],
      'payment_gateway' => $gateway->name(),
    ])->save();
    $this->audit->log('tenant.payments_configured', $tenant, null, ['gateway' => $gateway->name(), 'wallet_id' => $account['wallet_id']]);

    return redirect()->route('painel.configuracoes.edit', ['aba' => 'recebimentos'])->with('success', 'Conta de recebimento criada. Os valores das vendas serão repassados automaticamente.');
  }

  /**
   * Inicia a conexão OAuth da conta do organizador (ex.: Mercado Pago).
   */
  public function connectPayments(Request $request, string $gateway, PaymentGatewayManager $gateways)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);
    $driver = $this->oauthGateway($gateways, $gateway);

    if (!$this->canSwitchGateway($tenant)) {
      return $this->switchBlocked();
    }

    $state = Str::random(40);
    $request->session()->put('payments_oauth', ['state' => $state, 'tenant_id' => $tenant->id, 'gateway' => $gateway]);

    try {
      return redirect()->away($driver->authorizationUrl($state, route('painel.configuracoes.payments.callback', $gateway)));
    } catch (Throwable $e) {
      Log::error('Gateway OAuth indisponível', ['gateway' => $gateway, 'error' => $e->getMessage()]);

      return $this->toPaymentsTab()->with('error', 'Conexão indisponível no momento. Avise o suporte da plataforma.');
    }
  }

  /**
   * Retorno do OAuth: valida o state e troca o código pelas credenciais.
   */
  public function connectCallback(Request $request, string $gateway, PaymentGatewayManager $gateways)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);
    $driver = $this->oauthGateway($gateways, $gateway);

    $expected = $request->session()->pull('payments_oauth');
    $valid = is_array($expected)
      && $expected['tenant_id'] === $tenant->id
      && $expected['gateway'] === $gateway
      && hash_equals($expected['state'], (string) $request->query('state'));

    if (!$valid) {
      return $this->toPaymentsTab()->with('error', 'Sessão de conexão expirada ou inválida. Tente conectar novamente.');
    }
    if ($request->filled('error') || !$request->filled('code')) {
      return $this->toPaymentsTab()->with('error', 'A conexão com o ' . $driver->label() . ' foi cancelada.');
    }

    try {
      $driver->connect($tenant, (string) $request->query('code'), route('painel.configuracoes.payments.callback', $gateway));
    } catch (Throwable $e) {
      Log::error('Falha ao conectar conta de recebimento', ['tenant' => $tenant->id, 'gateway' => $gateway, 'error' => $e->getMessage()]);

      return $this->toPaymentsTab()->with('error', 'Não foi possível conectar a conta: ' . $e->getMessage());
    }

    $this->audit->log('tenant.payments_configured', $tenant, null, ['gateway' => $gateway, 'account_id' => $driver->accountId($tenant)]);

    return $this->toPaymentsTab()->with('success', 'Conta ' . $driver->label() . ' conectada. Os valores das vendas cairão direto na sua conta.');
  }

  /**
   * Desconecta a conta OAuth. Bloqueado com rifas em andamento ou Pix pendentes,
   * pois consultas e estornos dessas cobranças dependem das credenciais.
   */
  public function disconnectPayments(Request $request, PaymentGatewayManager $gateways)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);

    $gateway = in_array($tenant->payment_gateway, PaymentGatewayManager::DRIVERS, true)
      ? $gateways->driver($tenant->payment_gateway)
      : null;
    abort_unless($gateway instanceof ConnectsAccounts, 404);

    if (!$this->canSwitchGateway($tenant)) {
      return $this->switchBlocked();
    }

    $accountId = $gateway->accountId($tenant);
    $gateway->disconnect($tenant);
    $this->audit->log('tenant.payments_disconnected', $tenant, ['gateway' => $gateway->name(), 'account_id' => $accountId], null);

    return $this->toPaymentsTab()->with('success', 'Conta ' . $gateway->label() . ' desconectada.');
  }

  protected function oauthGateway(PaymentGatewayManager $gateways, string $name): ConnectsAccounts&PaymentGateway
  {
    abort_unless($gateways->isEnabled($name), 404);
    $driver = $gateways->driver($name);
    abort_unless($driver instanceof ConnectsAccounts, 404);

    return $driver;
  }

  /**
   * Trocar de gateway com vendas em andamento deixaria cobranças sem credenciais
   * para conciliação/estorno: só é permitido sem rifas ativas nem Pix pendentes.
   */
  protected function canSwitchGateway(Tenant $tenant): bool
  {
    if (!$tenant->hasPaymentAccount()) {
      return true;
    }

    $activeRaffles = $tenant->raffles()->withoutGlobalScopes()
      ->whereIn('status', [RaffleStatus::Published, RaffleStatus::SalesClosed, RaffleStatus::Drawn])
      ->exists();
    $pendingPayments = Payment::query()
      ->where('status', PaymentStatus::Pending)
      ->whereHas('order', fn($q) => $q->withoutGlobalScopes()->where('tenant_id', $tenant->id))
      ->exists();

    return !$activeRaffles && !$pendingPayments;
  }

  protected function switchBlocked()
  {
    return $this->toPaymentsTab()->with('error', 'Não é possível trocar a conta de recebimento com rifas em andamento ou Pix aguardando pagamento.');
  }

  protected function toPaymentsTab()
  {
    return redirect()->route('painel.configuracoes.edit', ['aba' => 'recebimentos']);
  }
}
