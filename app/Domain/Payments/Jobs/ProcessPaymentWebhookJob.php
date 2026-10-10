<?php

namespace App\Domain\Payments\Jobs;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\PaymentConfirmationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhookJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 5;

  public array $backoff = [10, 30, 60, 300];

  public function __construct(public int $webhookEventId, public ?string $gatewayPaymentId, public ?string $status) {}

  public function handle(PaymentConfirmationService $confirmations): void
  {
    $event = WebhookEvent::findOrFail($this->webhookEventId);
    if ($event->processed_at) {
      return;
    }

    $payment = $this->gatewayPaymentId
      ? Payment::query()->where('gateway', $event->origin)->where('gateway_id', $this->gatewayPaymentId)->first()
      : null;

    if ($payment && $this->status === PaymentStatus::Paid->value) {
      $confirmations->confirm($payment, $event->payload);
    } elseif ($payment && $this->status === PaymentStatus::Refunded->value && $payment->status !== PaymentStatus::Refunded) {
      $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
    }

    $event->forceFill(['processed_at' => now(), 'error' => $payment ? null : 'Cobrança não encontrada'])->save();
  }
}
