<?php

namespace App\Domain\Raffles\Jobs;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\RefundService;
use App\Domain\Tickets\TicketReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rifa cancelada/anulada: reembolso total de todos os pedidos pagos.
 */
class RefundRaffleJob implements ShouldQueue
{
  use Queueable;

  public int $timeout = 900;

  public function __construct(public int $raffleId, public string $reason) {}

  public function handle(RefundService $refunds, TicketReservationService $reservations): void
  {
    Order::query()->withoutGlobalScopes()->with('payment', 'raffle')
      ->where('raffle_id', $this->raffleId)
      ->whereIn('status', [OrderStatus::Paid, OrderStatus::AwaitingPayment])
      ->chunkById(100, function ($orders) use ($refunds, $reservations) {
        foreach ($orders as $order) {
          if ($order->status === OrderStatus::Paid && $order->payment?->status === PaymentStatus::Paid) {
            $refunds->refund($order->payment, $this->reason);
          } else {
            $reservations->release($order);
            $order->forceFill(['status' => OrderStatus::Cancelled])->save();
          }
        }
      });
  }
}
