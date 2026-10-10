<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Models\KycDocument;
use App\Domain\Tenants\TenantContext;
use App\Http\Controllers\Controller;
use App\Rules\CpfCnpj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
      'gateway' => config('saas.payment_gateway'),
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
   * Cria a subconta de recebimento (split) do organizador no gateway.
   */
  public function payments(Request $request, PaymentGatewayManager $gateways)
  {
    $tenant = $this->context->get();
    abort_unless($request->user()->isOwnerOf($tenant), 403);
    if ($tenant->hasPaymentAccount()) {
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

    try {
      $account = $gateways->default()->createSubaccount($tenant, $data);
    } catch (Throwable $e) {
      Log::error('Falha ao criar subconta', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);

      return back()->withInput()->with('error', 'Não foi possível criar a conta de recebimento: ' . $e->getMessage());
    }

    $tenant->forceFill([
      'asaas_account_id' => $account['account_id'],
      'asaas_wallet_id' => $account['wallet_id'],
      'asaas_api_key' => $account['api_key'],
      'payment_gateway' => $gateways->default()->name(),
    ])->save();
    $this->audit->log('tenant.payments_configured', $tenant, null, ['wallet_id' => $account['wallet_id']]);

    return redirect()->route('painel.configuracoes.edit', ['aba' => 'recebimentos'])->with('success', 'Conta de recebimento criada. Os valores das vendas serão repassados automaticamente.');
  }
}
