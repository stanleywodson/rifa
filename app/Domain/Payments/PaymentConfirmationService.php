<?php

namespace App\Domain\Payments;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Notifications\Mail\NewSaleMail;
use App\Domain\Notifications\Mail\PaymentConfirmedMail;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Raffles\Jobs\CloseSalesJob;
use App\Domain\Tickets\TicketReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PaymentConfirmationService
{
  public function __construct(
    protected TicketReservationService $reservations,
    protected RefundService $refunds,
    protected AuditLogger $audit,
  ) {}

  /**
   * Confirma um pagamento recebido. Idempotente: chamadas repetidas
   * (webhook duplicado, conciliação) não produzem efeito duplo.
   *
   * @return string paid | already_paid | refunded
   */
  public function confirm(Payment $payment, array $raw = []): string
  {
    $result = DB::transaction(function () use ($payment, $raw) {
      $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
      $order = Order::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->order_id);

      if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true) || $order->status === OrderStatus::Paid) {
        return 'already_paid';
      }

      // O dinheiro entrou: registra o pagamento antes de decidir pelos números.
      $payment->forceFill(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'payload' => $raw ?: $payment->payload])->save();

      $raffle = $order->raffle()->withoutGlobalScopes()->first();
      $order->setRelation('raffle', $raffle);

      // Reserva vigente: sempre confirma. Reserva expirada: só se a rifa ainda vende e os números seguem livres.
      $eligible = $order->status === OrderStatus::AwaitingPayment
        || ($order->status === OrderStatus::Expired && $raffle->isOnSale());

      if (!$eligible || !$this->reservations->markPaid($order)) {
        return 'late_conflict';
      }

      $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now()])->save();
      $this->audit->log('order.paid', $order, null, ['payment_id' => $payment->id, 'gateway_id' => $payment->gateway_id]);

      return 'paid';
    });

    if ($result === 'late_conflict') {
      $payment->refresh();
      $this->refunds->refund($payment, 'Pagamento após expiração da reserva; números indisponíveis.');

      return 'refunded';
    }

    if ($result === 'paid') {
      $order = Order::query()->withoutGlobalScopes()->with(['customer', 'raffle.tenant.users', 'items'])->find($payment->order_id);
      if ($order->customer->email) {
        Mail::to($order->customer->email)->queue(new PaymentConfirmedMail($order));
      }
      foreach ($order->raffle->tenant->users as $user) {
        Mail::to($user->email)->queue(new NewSaleMail($order));
      }
      CloseSalesJob::dispatch($order->raffle_id);
    }

    return $result;
  }
}
