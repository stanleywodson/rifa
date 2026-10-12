<?php

namespace App\Domain\Payments\Jobs;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Payments\PaymentGatewayManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhookJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 5;

  public array $backoff = [10, 30, 60, 300];

  public function __construct(public int $webhookEventId, public ?string $gatewayPaymentId, public ?string $status) {}

  public function handle(PaymentConfirmationService $confirmations, PaymentGatewayManager $gateways): void
  {
    $event = WebhookEvent::findOrFail($this->webhookEventId);
    if ($event->processed_at) {
      return;
    }

    $payment = $this->gatewayPaymentId
      ? Payment::query()->where('gateway', $event->origin)->where('gateway_id', $this->gatewayPaymentId)->first()
      : null;

    $status = $this->status;
    $data = $event->payload;
    if ($payment && $status === null) {
      // Webhook sem status (ex.: Mercado Pago): consulta a cobrança no gateway.
      // Falha de rede lança exceção e o job é tentado novamente.
      $charge = $gateways->driver($payment->gateway)->getCharge($payment);
      $status = $charge->status->value;
      $data = $charge->raw;
    }

    if ($payment && $status === PaymentStatus::Paid->value) {
      $confirmations->confirm($payment, $data);
    } elseif ($payment && $status === PaymentStatus::Refunded->value && $payment->status !== PaymentStatus::Refunded) {
      $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
    }

    $event->forceFill(['processed_at' => now(), 'error' => $payment ? null : 'Cobrança não encontrada'])->save();
  }
}
