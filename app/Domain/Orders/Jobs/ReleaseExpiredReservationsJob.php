<?php

namespace App\Domain\Orders\Jobs;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Tickets\TicketReservationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Agendado a cada minuto: devolve à grade os números de pedidos não pagos.
 */
class ReleaseExpiredReservationsJob implements ShouldQueue, ShouldBeUnique
{
  use Queueable;

  public function handle(TicketReservationService $reservations, PaymentGatewayManager $gateways, PaymentConfirmationService $confirmations): void
  {
    Order::query()->withoutGlobalScopes()
      ->where('status', OrderStatus::AwaitingPayment)
      ->where('expires_at', '<', now())
      ->with(['payment', 'raffle'])
      ->chunkById(200, function ($orders) use ($reservations, $gateways, $confirmations) {
        foreach ($orders as $order) {
          $payment = $order->payment;

          // Última checagem no gateway (webhook pode ter se perdido).
          if ($payment && $payment->gateway !== 'fake') {
            try {
              $charge = $gateways->driver($payment->gateway)->getCharge($payment);
              if ($charge->status === PaymentStatus::Paid) {
                $confirmations->confirm($payment, $charge->raw);

                continue;
              }
              $gateways->driver($payment->gateway)->cancelCharge($payment);
            } catch (Throwable $e) {
              Log::warning('Conciliação na expiração falhou', ['order' => $order->code, 'error' => $e->getMessage()]);
            }
          }

          DB::transaction(function () use ($order, $payment, $reservations) {
            $locked = Order::query()->withoutGlobalScopes()->lockForUpdate()->find($order->id);
            if ($locked->status !== OrderStatus::AwaitingPayment) {
              return;
            }
            $reservations->release($order);
            $locked->forceFill(['status' => OrderStatus::Expired])->save();
            $payment?->forceFill(['status' => PaymentStatus::Expired])->save();
          });
        }
      });
  }
}
