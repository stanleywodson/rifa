<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Jobs\ReleaseExpiredReservationsJob;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Domain\Tickets\Exceptions\NumbersUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
  use RefreshDatabase;

  public function test_checkout_reserves_numbers_and_creates_pix(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);

    $this->post(route('public.checkout.store', [$tenant->slug, $raffle->slug]), $this->buyer([
      'numbers' => '3,7,42',
      'accept_terms' => '1',
      'adult' => '1',
    ]))->assertRedirect();

    $order = $raffle->orders()->withoutGlobalScopes()->firstOrFail();
    $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
    $this->assertSame(3000, $order->total_cents);
    $this->assertSame([3, 7, 42], $order->items()->pluck('number')->all());
    $this->assertSame(3, $raffle->tickets()->where('status', TicketStatus::Reserved)->count());
    $this->assertNotEmpty($order->payment->pix_copy_paste);
    $this->assertSame(2, $order->payment->splits()->count());
  }

  public function test_minors_cannot_buy(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);

    $this->post(route('public.checkout.store', [$tenant->slug, $raffle->slug]), $this->buyer([
      'numbers' => '1', 'birthdate' => now()->subYears(17)->toDateString(), 'accept_terms' => '1', 'adult' => '1',
    ]))->assertSessionHasErrors('birthdate');
  }

  public function test_same_number_cannot_be_reserved_twice(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);

    $this->checkout($raffle, [10, 11]);

    $this->expectException(NumbersUnavailable::class);
    try {
      $this->checkout($raffle, [11, 12]);
    } finally {
      // nada foi reservado parcialmente
      $this->assertSame(TicketStatus::Free, $raffle->tickets()->where('number', 12)->first()->status);
    }
  }

  public function test_random_numbers_are_unique_and_free(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant, ['total_numbers' => 20]);

    $a = $this->checkout($raffle, [], 10);
    $b = $this->checkout($raffle, [], 10);

    $numbers = array_merge($a->items->pluck('number')->all(), $b->items->pluck('number')->all());
    $this->assertCount(20, array_unique($numbers));

    $this->expectException(NumbersUnavailable::class);
    $this->checkout($raffle, [], 1);
  }

  public function test_webhook_confirms_payment_once(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $order = $this->checkout($raffle, [5]);
    $payload = ['id' => 'evt_1', 'event' => 'PAYMENT_RECEIVED', 'payment' => ['id' => $order->payment->gateway_id], 'status' => 'paid'];

    $this->postJson(route('webhooks.payments', 'fake'), $payload, ['x-fake-token' => FakeGateway::WEBHOOK_TOKEN])->assertOk();
    $this->postJson(route('webhooks.payments', 'fake'), $payload, ['x-fake-token' => FakeGateway::WEBHOOK_TOKEN])
      ->assertOk()->assertJsonPath('duplicate', true);

    $order->refresh();
    $this->assertSame(OrderStatus::Paid, $order->status);
    $this->assertSame(TicketStatus::Paid, $raffle->tickets()->where('number', 5)->first()->status);
    $this->assertDatabaseCount('webhook_events', 1);
    $this->assertSame('already_paid', app(PaymentConfirmationService::class)->confirm($order->payment));
  }

  public function test_webhook_with_invalid_signature_is_rejected(): void
  {
    $this->postJson(route('webhooks.payments', 'fake'), ['id' => 'x'], ['x-fake-token' => 'errado'])->assertUnauthorized();
    $this->postJson(route('webhooks.payments', 'asaas'), ['id' => 'x'])->assertUnauthorized();
    $this->postJson(route('webhooks.payments', 'desconhecido'), [])->assertNotFound();
  }

  public function test_expired_reservations_are_released(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $order = $this->checkout($raffle, [8, 9]);

    $this->travel(config('saas.reservation_ttl') + 1)->minutes();
    (new ReleaseExpiredReservationsJob)->handle(app(\App\Domain\Tickets\TicketReservationService::class), app(\App\Domain\Payments\PaymentGatewayManager::class), app(PaymentConfirmationService::class));

    $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
    $this->assertSame(PaymentStatus::Expired, $order->payment->fresh()->status);
    $this->assertSame(0, $raffle->tickets()->where('status', '!=', TicketStatus::Free->value)->count());
  }

  public function test_late_payment_is_confirmed_when_numbers_still_free(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $order = $this->checkout($raffle, [20]);
    $this->expire();

    $this->assertSame('paid', app(PaymentConfirmationService::class)->confirm($order->payment->fresh()));
    $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
  }

  public function test_late_payment_is_refunded_when_number_was_sold_to_someone_else(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $late = $this->checkout($raffle, [30]);
    $this->expire();

    $other = $this->checkout($raffle, [30], buyer: ['cpf' => '52998224725']);
    app(PaymentConfirmationService::class)->confirm($other->payment);

    $this->assertSame('refunded', app(PaymentConfirmationService::class)->confirm($late->payment->fresh()));
    $this->assertSame(OrderStatus::Refunded, $late->fresh()->status);
    $this->assertSame(PaymentStatus::Refunded, $late->payment->fresh()->status);
    $this->assertSame($other->id, $raffle->tickets()->where('number', 30)->first()->order_id);
  }

  public function test_sales_closed_raffle_rejects_checkout(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $raffle->forceFill(['sales_end_at' => now()->subMinute()])->save();

    $this->post(route('public.checkout.store', [$tenant->slug, $raffle->slug]), $this->buyer([
      'numbers' => '1', 'accept_terms' => '1', 'adult' => '1',
    ]))->assertSessionHas('error');
    $this->assertDatabaseCount('orders', 0);
  }

  protected function expire(): void
  {
    $this->travel(config('saas.reservation_ttl') + 1)->minutes();
    (new ReleaseExpiredReservationsJob)->handle(app(\App\Domain\Tickets\TicketReservationService::class), app(\App\Domain\Payments\PaymentGatewayManager::class), app(PaymentConfirmationService::class));
  }
}
