<?php

namespace App\Domain\Orders;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Notifications\Mail\OrderCreatedMail;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\CheckoutUnavailable;
use App\Domain\Orders\Models\Customer;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\TicketReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckoutService
{
  public function __construct(
    protected TicketReservationService $reservations,
    protected PaymentGatewayManager $gateways,
    protected AuditLogger $audit,
  ) {}

  /**
   * Reserva os números e gera a cobrança Pix.
   *
   * @param  array{name: string, cpf: string, email: ?string, phone: string, birthdate: string}  $buyer
   * @param  int[]  $numbers  números escolhidos (vazio = aleatórios)
   */
  public function checkout(Raffle $raffle, array $buyer, array $numbers, int $randomQuantity = 0, ?string $ip = null): Order
  {
    if (!$raffle->tenant->isActive() || !$raffle->tenant->canReceivePayments()) {
      throw new CheckoutUnavailable('Este organizador não está habilitado para receber pagamentos.');
    }
    if (!$raffle->isOnSale()) {
      throw new CheckoutUnavailable('As vendas desta rifa não estão abertas.');
    }

    $quantity = $numbers ? count(array_unique($numbers)) : $randomQuantity;
    if ($quantity < max(1, $raffle->min_numbers_per_order) || $quantity > config('saas.max_numbers_per_order')) {
      throw new CheckoutUnavailable('Quantidade de números inválida para esta rifa.');
    }

    $expiresAt = now()->addMinutes(config('saas.reservation_ttl'));

    $order = DB::transaction(function () use ($raffle, $buyer, $numbers, $randomQuantity, $quantity, $expiresAt, $ip) {
      $customer = Customer::query()->updateOrCreate(
        ['tenant_id' => $raffle->tenant_id, 'cpf' => only_digits($buyer['cpf'])],
        [
          'name' => $buyer['name'],
          'email' => $buyer['email'] ?? null,
          'phone' => only_digits($buyer['phone']),
          'birthdate' => $buyer['birthdate'],
        ]
      );

      $order = Order::create([
        'tenant_id' => $raffle->tenant_id,
        'raffle_id' => $raffle->id,
        'customer_id' => $customer->id,
        'status' => OrderStatus::AwaitingPayment,
        'quantity' => $quantity,
        'subtotal_cents' => $quantity * $raffle->price_cents,
        'total_cents' => $quantity * $raffle->price_cents,
        'expires_at' => $expiresAt,
        'ip' => $ip,
      ]);

      $reserved = $numbers
        ? $this->reservations->reserve($raffle, $order, $numbers, $expiresAt)
        : $this->reservations->reserveRandom($raffle, $order, $randomQuantity, $expiresAt);

      $ticketIds = Ticket::query()->where('raffle_id', $raffle->id)->whereIn('number', $reserved)->pluck('id', 'number');
      OrderItem::insert(array_map(fn($n) => [
        'order_id' => $order->id,
        'ticket_id' => $ticketIds[$n],
        'number' => $n,
        'price_cents' => $raffle->price_cents,
      ], $reserved));

      return $order;
    });

    // Cobrança fora da transação: não segura locks durante a chamada HTTP.
    try {
      $this->createCharge($order);
    } catch (Throwable $e) {
      DB::transaction(function () use ($order) {
        $this->reservations->release($order);
        $order->forceFill(['status' => OrderStatus::Cancelled])->save();
      });
      Log::error('Falha ao gerar cobrança Pix', ['order' => $order->code, 'error' => $e->getMessage()]);
      throw new CheckoutUnavailable('Não foi possível gerar o Pix agora. Tente novamente em instantes.', previous: $e);
    }

    $this->audit->log('order.created', $order, null, [
      'raffle_id' => $raffle->id,
      'quantity' => $order->quantity,
      'total_cents' => $order->total_cents,
    ]);

    if ($order->customer->email) {
      Mail::to($order->customer->email)->queue(new OrderCreatedMail($order));
    }

    return $order->fresh(['items', 'payment', 'customer', 'raffle.tenant']);
  }

  protected function createCharge(Order $order): Payment
  {
    $tenant = $order->raffle->tenant;
    $gateway = $this->gateways->forTenant($tenant)
      ?? throw new CheckoutUnavailable('Este organizador não está habilitado para receber pagamentos.');
    $fee = $tenant->feePercent();
    $charge = $gateway->createPixCharge($order, $fee);

    $payment = Payment::create([
      'order_id' => $order->id,
      'gateway' => $gateway->name(),
      'gateway_id' => $charge->gatewayId,
      'method' => 'pix',
      'status' => PaymentStatus::Pending,
      'amount_cents' => $order->total_cents,
      'pix_qr_image' => $charge->qrImageBase64,
      'pix_copy_paste' => $charge->copyPaste,
      'payload' => $charge->raw,
    ]);

    $platform = (int) round($order->total_cents * $fee / 100);
    $payment->splits()->createMany([
      ['recipient' => 'platform', 'amount_cents' => $platform],
      ['recipient' => 'organizer', 'wallet_id' => $gateway->accountId($tenant), 'amount_cents' => $order->total_cents - $platform],
    ]);

    return $payment;
  }
}
