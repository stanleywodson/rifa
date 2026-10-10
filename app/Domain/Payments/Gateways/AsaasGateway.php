<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Orders\Models\Order;
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
use Illuminate\Support\Facades\Http;

/**
 * Integração Asaas (API v3): cobrança Pix pela conta da plataforma com split
 * para a subconta (walletId) do organizador. A plataforma retém apenas a taxa.
 *
 * @see https://docs.asaas.com/reference
 */
class AsaasGateway implements PaymentGateway
{
  public function __construct(protected array $config) {}

  public function name(): string
  {
    return 'asaas';
  }

  protected function http(): PendingRequest
  {
    if (blank($this->config['api_key'])) {
      throw new GatewayException('ASAAS_API_KEY não configurada.');
    }

    return Http::baseUrl($this->config['base_url'])
      ->withHeaders([
        'access_token' => $this->config['api_key'],
        'User-Agent' => config('app.name'),
      ])
      ->acceptJson()
      ->asJson()
      ->timeout(15)
      ->retry(2, 500, throw: false);
  }

  protected function check(Response $response, string $context): array
  {
    if ($response->failed()) {
      $message = collect($response->json('errors', []))->pluck('description')->implode('; ')
        ?: $response->json('message')
        ?: $response->body();
      throw new GatewayException("Asaas ({$context}): {$message}");
    }

    return $response->json() ?? [];
  }

  public function createPixCharge(Order $order, float $platformFeePercent): PixCharge
  {
    $order->loadMissing('customer', 'raffle.tenant');
    $customer = $order->customer;
    $tenant = $order->raffle->tenant;

    if (!$customer->gateway_customer_id) {
      $data = $this->check($this->http()->post('/customers', [
        'name' => $customer->name,
        'cpfCnpj' => $customer->cpf,
        'email' => $customer->email,
        'mobilePhone' => $customer->phone,
        'notificationDisabled' => true,
        'externalReference' => 'customer-' . $customer->id,
      ]), 'cliente');
      $customer->forceFill(['gateway_customer_id' => $data['id']])->save();
    }

    $payload = [
      'customer' => $customer->gateway_customer_id,
      'billingType' => 'PIX',
      'value' => round($order->total_cents / 100, 2),
      'dueDate' => now()->toDateString(),
      'description' => mb_substr("Rifa: {$order->raffle->title} — {$order->quantity} número(s)", 0, 500),
      'externalReference' => $order->code,
    ];

    if ($tenant->hasPaymentAccount()) {
      $payload['split'] = [[
        'walletId' => $tenant->asaas_wallet_id,
        'percentualValue' => round(100 - $platformFeePercent, 2),
      ]];
    }

    $charge = $this->check($this->http()->post('/payments', $payload), 'cobrança');
    try {
      $qr = $this->check($this->http()->get("/payments/{$charge['id']}/pixQrCode"), 'QR Code Pix');
    } catch (GatewayException $e) {
      // Sem QR Code a cobrança é inútil: remove para não deixar cobrança órfã no Asaas.
      $this->http()->delete("/payments/{$charge['id']}");
      throw $e;
    }

    return new PixCharge(
      gatewayId: $charge['id'],
      status: $this->mapStatus($charge['status'] ?? 'PENDING'),
      qrImageBase64: $qr['encodedImage'] ?? null,
      copyPaste: $qr['payload'] ?? null,
      raw: $charge,
    );
  }

  public function getCharge(Payment $payment): PixCharge
  {
    $charge = $this->check($this->http()->get("/payments/{$payment->gateway_id}"), 'consulta');

    return new PixCharge($charge['id'], $this->mapStatus($charge['status']), raw: $charge);
  }

  public function cancelCharge(Payment $payment): void
  {
    $this->http()->delete("/payments/{$payment->gateway_id}");
  }

  public function refund(Payment $payment, string $reason): array
  {
    return $this->check($this->http()->post("/payments/{$payment->gateway_id}/refund", [
      'value' => round($payment->amount_cents / 100, 2),
      'description' => mb_substr($reason, 0, 255),
    ]), 'estorno');
  }

  public function createSubaccount(Tenant $tenant, array $data): array
  {
    $account = $this->check($this->http()->post('/accounts', array_filter([
      'name' => $tenant->name,
      'email' => $data['email'],
      'cpfCnpj' => only_digits($tenant->document),
      'birthDate' => $data['birth_date'] ?? null,
      'companyType' => $data['company_type'] ?? null,
      'mobilePhone' => only_digits($data['phone']),
      'incomeValue' => $data['income_value'],
      'address' => $data['address'],
      'addressNumber' => $data['address_number'],
      'province' => $data['province'],
      'postalCode' => only_digits($data['postal_code']),
    ])), 'subconta');

    return [
      'account_id' => $account['id'],
      'wallet_id' => $account['walletId'],
      'api_key' => $account['apiKey'] ?? null,
    ];
  }

  public function parseWebhook(Request $request): ?WebhookPayload
  {
    $token = (string) $this->config['webhook_token'];
    if ($token === '' || !hash_equals($token, (string) $request->header('asaas-access-token'))) {
      return null;
    }

    $event = (string) $request->input('event');
    $payment = $request->input('payment', []);

    $status = match ($event) {
      'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => PaymentStatus::Paid,
      'PAYMENT_REFUNDED', 'PAYMENT_RECEIVED_IN_CASH_UNDONE' => PaymentStatus::Refunded,
      'PAYMENT_OVERDUE', 'PAYMENT_DELETED' => PaymentStatus::Expired,
      default => null,
    };

    return new WebhookPayload(
      // "id" do evento é único por entrega lógica; fallback evento+cobrança
      eventId: (string) ($request->input('id') ?: $event . ':' . ($payment['id'] ?? '')),
      eventType: $event,
      gatewayPaymentId: $payment['id'] ?? null,
      status: $status,
      raw: $request->all(),
    );
  }

  protected function mapStatus(string $status): PaymentStatus
  {
    return match ($status) {
      'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH' => PaymentStatus::Paid,
      'REFUNDED', 'REFUND_REQUESTED', 'REFUND_IN_PROGRESS' => PaymentStatus::Refunded,
      'OVERDUE' => PaymentStatus::Expired,
      default => PaymentStatus::Pending,
    };
  }
}
