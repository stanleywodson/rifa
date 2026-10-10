<?php

namespace App\Domain\Payments\Jobs;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Payments\PaymentGatewayManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback de conciliação: consulta no gateway cobranças pendentes ou
 * recém-expiradas (últimas 24 h) caso o webhook não tenha chegado.
 */
class ReconcilePendingPaymentsJob implements ShouldQueue, ShouldBeUnique
{
  use Queueable;

  public function handle(PaymentGatewayManager $gateways, PaymentConfirmationService $confirmations): void
  {
    Payment::query()
      ->where('gateway', '!=', 'fake')
      ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Expired])
      ->where('created_at', '>=', now()->subDay())
      ->where('created_at', '<=', now()->subMinutes(2))
      ->chunkById(100, function ($payments) use ($gateways, $confirmations) {
        foreach ($payments as $payment) {
          try {
            $charge = $gateways->driver($payment->gateway)->getCharge($payment);
            if ($charge->status === PaymentStatus::Paid) {
              $confirmations->confirm($payment, $charge->raw);
            }
          } catch (Throwable $e) {
            Log::warning('Conciliação falhou', ['payment' => $payment->id, 'error' => $e->getMessage()]);
          }
        }
      });
  }
}
