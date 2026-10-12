<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Contracts\ConnectsAccounts;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\PixCharge;
use App\Domain\Payments\Data\WebhookPayload;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\GatewayException;
use App\Domain\Payments\Models\Payment;
use App\Domain\Tenants\Models\Tenant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integração Mercado Pago (modelo marketplace). O organizador conecta a própria
 * conta via OAuth; a cobrança Pix é criada com o access token dele e a taxa da
 * plataforma vai como "application_fee" — o restante cai direto na conta do
 * organizador (split).
 *
 * @see https://www.mercadopago.com.br/developers/pt/docs/split-payments/landing
 */
class MercadoPagoGateway implements PaymentGateway, ConnectsAccounts
{
  // O Mercado Pago exige ao menos 30 minutos de validade para cobranças Pix.
  protected const MIN_PIX_EXPIRATION_MINUTES = 31;

  // Renova o token com antecedência (validade de 180 dias).
  protected const REFRESH_BEFORE_DAYS = 7;

  public function __construct(protected array $config) {}

  public function name(): string
  {
    return 'mercadopago';
  }

  public function label(): string
  {
    return 'Mercado Pago';
  }

  public function isConnected(Tenant $tenant): bool
  {
    return $tenant->payment_gateway === $this->name() && filled($tenant->mp_user_id) && filled($tenant->mp_access_token);
  }

  public function accountId(Tenant $tenant): ?string
  {
    return $this->isConnected($tenant) ? $tenant->mp_user_id : null;
  }

  /*
  |--------------------------------------------------------------------------
  | OAuth (conexão da conta do organizador)
  |--------------------------------------------------------------------------
  */

  public function authorizationUrl(string $state, string $redirectUri): string
  {
    $this->ensureApplication();

    return $this->config['auth_url'] . '?' . http_build_query([
      'client_id' => $this->config['client_id'],
      'response_type' => 'code',
      'platform_id' => 'mp',
      'state' => $state,
      'redirect_uri' => $redirectUri,
    ]);
  }

  public function connect(Tenant $tenant, string $code, string $redirectUri): void
  {
    $this->ensureApplication();

    $data = $this->check($this->oauth([
      'grant_type' => 'authorization_code',
      'code' => $code,
      'redirect_uri' => $redirectUri,
    ]), 'conexão da conta');

    $this->storeCredentials($tenant, $data, ['payment_gateway' => $this->name()]);
  }

  public function disconnect(Tenant $tenant): void
  {
    // Volta para a conta Asaas/simulada anterior, se houver.
    $previous = $tenant->asaas_wallet_id ? (str_starts_with($tenant->asaas_wallet_id, 'fake_') ? 'fake' : 'asaas') : null;

    $tenant->forceFill([
      'payment_gateway' => $previous,
      'mp_user_id' => null,
      'mp_access_token' => null,
      'mp_refresh_token' => null,
      'mp_public_key' => null,
      'mp_token_expires_at' => null,
      'mp_live_mode' => false,
    ])->save();
  }

  /**
   * Renova o access token do organizador com o refresh token.
   */
  public function refreshToken(Tenant $tenant): void
  {
    if (blank($tenant->mp_refresh_token)) {
      throw new GatewayException('Mercado Pago: conta sem refresh token; conecte novamente.');
    }

    $data = $this->check($this->oauth([
      'grant_type' => 'refresh_token',
      'refresh_token' => $tenant->mp_refresh_token,
    ]), 'renovação do token');

    $this->storeCredentials($tenant, $data);
  }

  public function tokenNeedsRefresh(Tenant $tenant): bool
  {
    return $tenant->mp_token_expires_at !== null
      && $tenant->mp_token_expires_at->lt(now()->addDays(self::REFRESH_BEFORE_DAYS));
  }

  protected function oauth(array $params): Response
  {
    $params += [
      'client_id' => $this->config['client_id'],
      'client_secret' => $this->config['client_secret'],
    ];
    if (!empty($this->config['test_token'])) {
      $params['test_token'] = 'true';
    }

    return Http::baseUrl($this->config['base_url'])->acceptJson()->asForm()->timeout(15)->post('/oauth/token', $params);
  }

  protected function storeCredentials(Tenant $tenant, array $data, array $extra = []): void
  {
    if (blank($data['access_token'] ?? null) || blank($data['user_id'] ?? null)) {
      throw new GatewayException('Mercado Pago: resposta de autorização sem credenciais.');
    }

    $tenant->forceFill($extra + [
      'mp_user_id' => (string) $data['user_id'],
      'mp_access_token' => $data['access_token'],
      'mp_refresh_token' => $data['refresh_token'] ?? $tenant->mp_refresh_token,
      'mp_public_key' => $data['public_key'] ?? $tenant->mp_public_key,
      'mp_token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : null,
      'mp_live_mode' => (bool) ($data['live_mode'] ?? false),
    ])->save();
  }

  protected function ensureApplication(): void
  {
    if (blank($this->config['client_id']) || blank($this->config['client_secret'])) {
      throw new GatewayException('MERCADOPAGO_CLIENT_ID/MERCADOPAGO_CLIENT_SECRET não configurados.');
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Cobranças
  |--------------------------------------------------------------------------
  */

  protected function http(Tenant $tenant): PendingRequest
  {
    if (!$this->isConnected($tenant)) {
      throw new GatewayException('Mercado Pago: conta do organizador não conectada.');
    }
    if ($this->tokenNeedsRefresh($tenant)) {
      try {
        $this->refreshToken($tenant);
      } catch (GatewayException $e) {
        // O token atual ainda pode estar válido; segue e deixa a API decidir.
        Log::warning('Falha ao renovar token Mercado Pago', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
      }
    }

    return Http::baseUrl($this->config['base_url'])
      ->withToken($tenant->mp_access_token)
      ->withHeaders(['User-Agent' => config('app.name')])
      ->acceptJson()
      ->asJson()
      ->timeout(15)
      ->retry(2, 500, throw: false);
  }

  protected function check(Response $response, string $context): array
  {
    if ($response->failed()) {
      $message = collect($response->json('cause', []))->pluck('description')->filter()->implode('; ')
        ?: $response->json('message')
        ?: $response->json('error_description')
        ?: $response->body();
      throw new GatewayException("Mercado Pago ({$context}): {$message}");
    }

    return $response->json() ?? [];
  }

  public function createPixCharge(Order $order, float $platformFeePercent): PixCharge
  {
    $order->loadMissing('customer', 'raffle.tenant');
    $customer = $order->customer;
    $tenant = $order->raffle->tenant;

    if (blank($customer->email)) {
      throw new GatewayException('Mercado Pago: o e-mail do comprador é obrigatório.');
    }

    $names = preg_split('/\s+/', trim($customer->name), 2);
    $expiresAt = $order->expires_at->copy()->max(now()->addMinutes(self::MIN_PIX_EXPIRATION_MINUTES));
    // Mesmo arredondamento do split registrado no pedido (CheckoutService).
    $feeCents = (int) round($order->total_cents * $platformFeePercent / 100);

    $payload = [
      'transaction_amount' => round($order->total_cents / 100, 2),
      'payment_method_id' => 'pix',
      'description' => mb_substr("Rifa: {$order->raffle->title} — {$order->quantity} número(s)", 0, 255),
      'external_reference' => $order->code,
      'date_of_expiration' => $expiresAt->copy()->setTimezone('America/Sao_Paulo')->format('Y-m-d\TH:i:s.vP'),
      'application_fee' => round($feeCents / 100, 2),
      'payer' => [
        'email' => $this->payerEmail($customer->email),
        'first_name' => $names[0],
        'last_name' => $names[1] ?? $names[0],
        'identification' => ['type' => 'CPF', 'number' => only_digits($customer->cpf)],
      ],
      'metadata' => ['order_code' => $order->code, 'tenant_id' => $tenant->id],
    ];

    // O Mercado Pago só aceita URL pública (em dev, use ngrok/sail share).
    $notificationUrl = route('webhooks.payments', $this->name());
    if (str_starts_with($notificationUrl, 'https://')) {
      $payload['notification_url'] = $notificationUrl;
    }

    $charge = $this->check(
      $this->http($tenant)->withHeaders(['X-Idempotency-Key' => 'order-' . $order->code])->post('/v1/payments', $payload),
      'cobrança'
    );

    $qr = Arr::get($charge, 'point_of_interaction.transaction_data', []);
    if (blank($qr['qr_code'] ?? null)) {
      // Sem QR Code a cobrança é inútil: cancela para não deixar cobrança órfã.
      $this->http($tenant)->put("/v1/payments/{$charge['id']}", ['status' => 'cancelled']);
      throw new GatewayException('Mercado Pago (QR Code Pix): ' . ($charge['status_detail'] ?? 'QR Code não retornado') . '. Verifique se a conta do organizador tem chave Pix cadastrada.');
    }

    return new PixCharge(
      gatewayId: (string) $charge['id'],
      status: $this->mapStatus($charge['status'] ?? 'pending'),
      qrImageBase64: $qr['qr_code_base64'] ?? null,
      copyPaste: $qr['qr_code'],
      raw: $this->summary($charge),
    );
  }

  protected function payerEmail(string $email): string
  {
    $testPayer = $this->config['test_payer_email'] ?? null;

    return filled($testPayer) && !app()->isProduction() ? $testPayer : $email;
  }

  public function getCharge(Payment $payment): PixCharge
  {
    $charge = $this->check($this->http($this->tenantOf($payment))->get("/v1/payments/{$payment->gateway_id}"), 'consulta');

    return new PixCharge((string) $charge['id'], $this->mapStatus($charge['status'] ?? 'pending'), raw: $this->summary($charge));
  }

  public function cancelCharge(Payment $payment): void
  {
    $this->http($this->tenantOf($payment))->put("/v1/payments/{$payment->gateway_id}", ['status' => 'cancelled']);
  }

  public function refund(Payment $payment, string $reason): array
  {
    // Estorno total; a application_fee é devolvida proporcionalmente pelo Mercado Pago.
    return $this->check(
      $this->http($this->tenantOf($payment))
        ->withHeaders(['X-Idempotency-Key' => 'refund-' . $payment->id])
        ->post("/v1/payments/{$payment->gateway_id}/refunds", (object) []),
      'estorno'
    );
  }

  protected function tenantOf(Payment $payment): Tenant
  {
    return $payment->loadMissing('order.raffle.tenant')->order->raffle->tenant;
  }

  /**
   * Dados relevantes da cobrança (sem a imagem do QR Code, que já fica no pagamento).
   */
  protected function summary(array $charge): array
  {
    return Arr::only($charge, [
      'id', 'status', 'status_detail', 'external_reference', 'transaction_amount', 'date_created',
      'date_approved', 'date_of_expiration', 'collector_id', 'live_mode',
    ]) + ['ticket_url' => Arr::get($charge, 'point_of_interaction.transaction_data.ticket_url')];
  }

  /*
  |--------------------------------------------------------------------------
  | Webhook
  |--------------------------------------------------------------------------
  */

  /**
   * O webhook do Mercado Pago só avisa que a cobrança mudou: o status é sempre
   * consultado na API (status null), então um aviso forjado não confirma nada.
   * Com MERCADOPAGO_WEBHOOK_SECRET a assinatura x-signature é obrigatória.
   */
  public function parseWebhook(Request $request): ?WebhookPayload
  {
    $type = (string) ($request->input('type') ?: $request->query('topic', $request->input('topic', '')));
    // ?data.id= chega como data_id (o PHP troca "." por "_" na query string)
    $dataId = (string) ($request->query('data_id') ?? $request->input('data.id') ?? ($type === 'payment' ? $request->query('id') : null) ?? '');

    if (!$this->validSignature($request, $dataId)) {
      return null;
    }

    $action = (string) $request->input('action', $type);

    return new WebhookPayload(
      eventId: (string) ($request->input('id') ?: "{$action}:{$dataId}"),
      eventType: $action,
      gatewayPaymentId: $type === 'payment' && $dataId !== '' ? $dataId : null,
      status: null,
      raw: $request->all(),
    );
  }

  protected function validSignature(Request $request, string $dataId): bool
  {
    $secret = (string) ($this->config['webhook_secret'] ?? '');
    if ($secret === '') {
      // Sem segredo só em desenvolvimento: o status ainda é conferido na API.
      return !app()->isProduction();
    }

    $parts = [];
    foreach (explode(',', (string) $request->header('x-signature')) as $part) {
      [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
      $parts[$key] = $value;
    }
    if (empty($parts['ts']) || empty($parts['v1'])) {
      return false;
    }

    // Manifesto: id:[data.id];request-id:[x-request-id];ts:[ts]; (partes ausentes são omitidas)
    $manifest = '';
    if ($dataId !== '') {
      $manifest .= 'id:' . (ctype_alnum($dataId) ? strtolower($dataId) : $dataId) . ';';
    }
    if ($request->hasHeader('x-request-id')) {
      $manifest .= 'request-id:' . $request->header('x-request-id') . ';';
    }
    $manifest .= 'ts:' . $parts['ts'] . ';';

    return hash_equals(hash_hmac('sha256', $manifest, $secret), (string) $parts['v1']);
  }

  protected function mapStatus(string $status): PaymentStatus
  {
    return match ($status) {
      'approved' => PaymentStatus::Paid,
      'refunded', 'charged_back' => PaymentStatus::Refunded,
      'cancelled', 'rejected' => PaymentStatus::Expired,
      default => PaymentStatus::Pending,
    };
  }
}
