<?php

namespace App\Domain\Payments;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Domain\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefundService
{
  public function __construct(protected PaymentGatewayManager $gateways, protected AuditLogger $audit) {}

  /**
   * Estorna um pagamento recebido e devolve os números (se ainda deste pedido).
   */
  public function refund(Payment $payment, string $reason): Refund
  {
    $refund = Refund::create([
      'payment_id' => $payment->id,
      'reason' => $reason,
      'amount_cents' => $payment->amount_cents,
      'status' => 'pending',
    ]);

    try {
      $response = $this->gateways->driver($payment->gateway)->refund($payment, $reason);
      $refund->forceFill(['status' => 'done', 'payload' => $response])->save();
    } catch (Throwable $e) {
      // Fica pendente para tratamento manual pelo financeiro.
      $refund->forceFill(['status' => 'failed', 'payload' => ['error' => $e->getMessage()]])->save();
      Log::error('Falha no estorno', ['payment' => $payment->id, 'error' => $e->getMessage()]);
    }

    DB::transaction(function () use ($payment, $refund) {
      if ($refund->status === 'done') {
        $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
      }
      $order = Order::query()->withoutGlobalScopes()->find($payment->order_id);
      Ticket::query()->where('order_id', $order->id)->where('status', '!=', TicketStatus::Free)
        ->update(['status' => TicketStatus::Free, 'order_id' => null, 'reserved_until' => null, 'paid_at' => null]);
      $order->forceFill(['status' => OrderStatus::Refunded])->save();

      $this->audit->log('payment.refund_' . $refund->status, $payment, null, [
        'order' => $order->code,
        'amount_cents' => $refund->amount_cents,
        'reason' => $refund->reason,
      ], $order->tenant_id);
    });

    return $refund;
  }
}
