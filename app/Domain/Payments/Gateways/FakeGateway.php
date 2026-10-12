<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Contracts\CreatesSubaccounts;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\PixCharge;
use App\Domain\Payments\Data\WebhookPayload;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Tenants\Models\Tenant;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gateway simulado para desenvolvimento e testes. O pagamento é confirmado
 * pelo botão "Simular pagamento" (somente ambiente local) ou nos testes.
 */
class FakeGateway implements PaymentGateway, CreatesSubaccounts
{
  public const WEBHOOK_TOKEN = 'fake-webhook-token';

  public function name(): string
  {
    return 'fake';
  }

  public function label(): string
  {
    return 'Modo de testes';
  }

  public function isConnected(Tenant $tenant): bool
  {
    return $tenant->payment_gateway === $this->name() && filled($tenant->asaas_wallet_id);
  }

  public function accountId(Tenant $tenant): ?string
  {
    return $this->isConnected($tenant) ? $tenant->asaas_wallet_id : null;
  }

  public function createPixCharge(Order $order, float $platformFeePercent): PixCharge
  {
    $id = 'fake_' . Str::lower(Str::random(16));
    $copyPaste = '00020126580014BR.GOV.BCB.PIX0136' . Str::uuid() . '5204000053039865406'
      . number_format($order->total_cents / 100, 2, '.', '') . '5802BR5913RIFA SIMULADA6009SAO PAULO62070503***6304ABCD';

    return new PixCharge($id, PaymentStatus::Pending, $this->qrImage($copyPaste), $copyPaste, ['id' => $id, 'fake' => true]);
  }

  public function getCharge(Payment $payment): PixCharge
  {
    return new PixCharge($payment->gateway_id, $payment->status);
  }

  public function cancelCharge(Payment $payment): void {}

  public function refund(Payment $payment, string $reason): array
  {
    return ['id' => $payment->gateway_id, 'status' => 'REFUNDED', 'fake' => true];
  }

  public function createSubaccount(Tenant $tenant, array $data): array
  {
    return [
      'account_id' => 'fake_acc_' . $tenant->id,
      'wallet_id' => 'fake_wallet_' . Str::lower(Str::random(10)),
      'api_key' => null,
    ];
  }

  public function parseWebhook(Request $request): ?WebhookPayload
  {
    if (!hash_equals(self::WEBHOOK_TOKEN, (string) $request->header('x-fake-token'))) {
      return null;
    }

    return new WebhookPayload(
      eventId: (string) $request->input('id'),
      eventType: (string) $request->input('event', 'PAYMENT_RECEIVED'),
      gatewayPaymentId: $request->input('payment.id'),
      status: PaymentStatus::tryFrom((string) $request->input('status', 'paid')),
      raw: $request->all(),
    );
  }

  protected function qrImage(string $content): ?string
  {
    if (!extension_loaded('gd')) {
      return null;
    }

    return base64_encode((new Writer(new GDLibRenderer(260)))->writeString($content));
  }
}
