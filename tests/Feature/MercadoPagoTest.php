<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\CheckoutUnavailable;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Jobs\RefreshMercadoPagoTokensJob;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Payments\RefundService;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
  use RefreshDatabase;

  protected const SECRET = 'whsecret';

  /**
   * Status devolvido pela consulta GET /v1/payments/{id} (mutável durante o teste).
   */
  protected string $remoteStatus = 'approved';

  protected function setUp(): void
  {
    parent::setUp();
    config([
      'saas.payment_gateway' => 'asaas',
      'saas.payment_gateways' => ['mercadopago'],
      'saas.asaas.api_key' => 'asaas-test',
      'saas.mercadopago.env' => 'sandbox',
      'saas.mercadopago.client_id' => 'client-id',
      'saas.mercadopago.client_secret' => 'client-secret',
      'saas.mercadopago.webhook_secret' => self::SECRET,
    ]);
  }

  /**
   * @return array{0: Tenant, 1: User}
   */
  protected function mpOrganizer(array $attributes = []): array
  {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->mercadoPago()->create($attributes);
    $tenant->users()->attach($user, ['role' => TenantRole::Owner->value]);

    return [$tenant, $user];
  }

  protected function fakeCharge(array $overrides = []): void
  {
    Http::fake([
      'api.mercadopago.com/v1/payments' => Http::response($overrides + [
        'id' => 987654321,
        'status' => 'pending',
        'status_detail' => 'pending_waiting_transfer',
        'point_of_interaction' => ['transaction_data' => [
          'qr_code' => '00020126580014br.gov.bcb.pix',
          'qr_code_base64' => 'iVBORw0KGgo=',
          'ticket_url' => 'https://www.mercadopago.com.br/payments/987654321/ticket',
        ]],
      ], 201),
      'api.mercadopago.com/v1/payments/987654321' => fn() => Http::response(['id' => 987654321, 'status' => $this->remoteStatus]),
    ]);
  }

  protected function signedWebhook(string $paymentId, array $body = [], ?string $secret = null): TestResponse
  {
    $ts = (string) time();
    $signature = hash_hmac('sha256', "id:{$paymentId};request-id:req-1;ts:{$ts};", $secret ?? self::SECRET);

    return $this->postJson(
      route('webhooks.payments', 'mercadopago') . '?data.id=' . $paymentId . '&type=payment',
      $body + ['id' => 'notif-' . $paymentId, 'action' => 'payment.updated', 'type' => 'payment', 'data' => ['id' => $paymentId]],
      ['x-signature' => "ts={$ts},v1={$signature}", 'x-request-id' => 'req-1'],
    );
  }

  // --- Cobrança e split -----------------------------------------------------

  public function test_pix_charge_uses_seller_token_and_platform_fee_as_application_fee(): void
  {
    [$tenant] = $this->mpOrganizer(['fee_percent' => 10]);
    $raffle = $this->publishedRaffle($tenant, ['price_cents' => 1000]);
    $this->fakeCharge();

    $order = $this->checkout($raffle, [1, 2]);

    $this->assertSame('mercadopago', $order->payment->gateway);
    $this->assertSame('987654321', $order->payment->gateway_id);
    $this->assertSame('00020126580014br.gov.bcb.pix', $order->payment->pix_copy_paste);
    $this->assertSame('iVBORw0KGgo=', $order->payment->pix_qr_image);
    $this->assertArrayNotHasKey('point_of_interaction', $order->payment->payload);

    Http::assertSent(function (HttpRequest $r) use ($order) {
      return $r->method() === 'POST' && str_ends_with($r->url(), '/v1/payments')
        && $r->hasHeader('Authorization', 'Bearer APP_USR-seller-token')
        && $r->hasHeader('X-Idempotency-Key', 'order-' . $order->code)
        && $r['payment_method_id'] === 'pix'
        && $r['transaction_amount'] == 20.0
        && $r['application_fee'] == 2.0
        && $r['external_reference'] === $order->code
        && $r['payer']['email'] === 'comprador@example.com'
        && $r['payer']['identification']['type'] === 'CPF';
    });

    $splits = $order->payment->splits->keyBy('recipient');
    $this->assertSame(200, $splits['platform']->amount_cents);
    $this->assertSame(1800, $splits['organizer']->amount_cents);
    $this->assertSame('123456789', $splits['organizer']->wallet_id);
  }

  public function test_test_payer_email_replaces_buyer_email_outside_production(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    config(['saas.mercadopago.test_payer_email' => 'test_user_1@testuser.com']);
    $this->fakeCharge();

    $order = $this->checkout($raffle, [1]);

    Http::assertSent(fn(HttpRequest $r) => str_ends_with($r->url(), '/v1/payments') && $r['payer']['email'] === 'test_user_1@testuser.com');
    $this->assertSame('comprador@example.com', $order->customer->email);
  }

  public function test_pix_expiration_respects_mercado_pago_minimum(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->fakeCharge();

    $this->checkout($raffle, [1]);

    Http::assertSent(fn(HttpRequest $r) => str_ends_with($r->url(), '/v1/payments')
      && Carbon::parse($r['date_of_expiration'])->gte(now()->addMinutes(30)));
  }

  public function test_charge_without_qr_code_is_cancelled_and_numbers_released(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    Http::fake([
      'api.mercadopago.com/v1/payments' => Http::response(['id' => 555, 'status' => 'rejected', 'status_detail' => 'rejected_by_bank'], 201),
      'api.mercadopago.com/v1/payments/555' => Http::response(['id' => 555, 'status' => 'cancelled']),
    ]);

    try {
      $this->checkout($raffle, [1]);
      $this->fail('Checkout deveria falhar');
    } catch (CheckoutUnavailable) {
    }

    Http::assertSent(fn(HttpRequest $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/v1/payments/555') && $r['status'] === 'cancelled');
    $this->assertSame('free', $raffle->tickets()->where('number', 1)->first()->status->value);
  }

  public function test_buyer_email_is_required_for_mercado_pago_checkout(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->forgetTenant();
    Http::fake();

    $this->post(route('public.checkout.store', [$tenant->slug, $raffle->slug]), $this->buyer(['email' => '']) + [
      'numbers' => '1', 'accept_terms' => '1', 'adult' => '1',
    ])->assertSessionHasErrors('email');

    $this->get(route('public.checkout', [$tenant->slug, $raffle->slug, 'numeros' => '1']))
      ->assertOk()->assertDontSee('opcional, para receber o comprovante');
    Http::assertNothingSent();
  }

  public function test_mercado_pago_account_is_ignored_when_gateway_is_disabled(): void
  {
    [$tenant] = $this->mpOrganizer();
    $this->assertTrue($tenant->canReceivePayments());

    config(['saas.payment_gateways' => []]);

    $this->assertFalse($tenant->fresh()->canReceivePayments());
    $this->assertTrue($tenant->fresh()->hasStalePaymentAccount());
  }

  public function test_expiring_token_is_refreshed_before_charging(): void
  {
    [$tenant] = $this->mpOrganizer(['mp_token_expires_at' => now()->addDay()]);
    $raffle = $this->publishedRaffle($tenant);
    Http::fake([
      'api.mercadopago.com/oauth/token' => Http::response([
        'access_token' => 'APP_USR-new-token', 'refresh_token' => 'TG-new-refresh', 'user_id' => 123456789,
        'public_key' => 'APP_USR-public-key', 'expires_in' => 15552000, 'live_mode' => false,
      ]),
      'api.mercadopago.com/v1/payments' => Http::response(['id' => 1, 'status' => 'pending', 'point_of_interaction' => ['transaction_data' => ['qr_code' => 'pix']]], 201),
    ]);

    $this->checkout($raffle, [1]);

    $tenant->refresh();
    $this->assertSame('APP_USR-new-token', $tenant->mp_access_token);
    $this->assertSame('TG-new-refresh', $tenant->mp_refresh_token);
    $this->assertTrue($tenant->mp_token_expires_at->gt(now()->addDays(170)));
    Http::assertSent(fn(HttpRequest $r) => str_ends_with($r->url(), '/oauth/token') && $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'TG-seller-refresh');
    Http::assertSent(fn(HttpRequest $r) => str_ends_with($r->url(), '/v1/payments') && $r->hasHeader('Authorization', 'Bearer APP_USR-new-token'));
  }

  public function test_scheduled_job_refreshes_tokens_close_to_expiration(): void
  {
    [$expiring] = $this->mpOrganizer(['mp_token_expires_at' => now()->addDays(10)]);
    [$fresh] = $this->mpOrganizer(['mp_token_expires_at' => now()->addDays(120), 'mp_user_id' => '999']);
    Http::fake(['api.mercadopago.com/oauth/token' => Http::response(['access_token' => 'APP_USR-renewed', 'user_id' => 123456789, 'expires_in' => 15552000])]);

    app()->call([new RefreshMercadoPagoTokensJob, 'handle']);

    $this->assertSame('APP_USR-renewed', $expiring->fresh()->mp_access_token);
    $this->assertSame('APP_USR-seller-token', $fresh->fresh()->mp_access_token);
    Http::assertSentCount(1);
  }

  // --- Webhook --------------------------------------------------------------

  public function test_signed_webhook_confirms_payment_after_checking_the_api(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->fakeCharge();
    $order = $this->checkout($raffle, [7]);
    $this->forgetTenant();

    $this->signedWebhook('987654321')->assertOk()->assertJson(['received' => true]);
    $this->signedWebhook('987654321')->assertOk()->assertJson(['duplicate' => true]);

    $order->refresh();
    $this->assertSame(OrderStatus::Paid, $order->status);
    $this->assertSame(PaymentStatus::Paid, $order->payment->status);
    $this->assertSame('paid', $raffle->tickets()->where('number', 7)->first()->status->value);
    $this->assertSame(1, WebhookEvent::query()->where('origin', 'mercadopago')->count());
    Http::assertSent(fn(HttpRequest $r) => $r->method() === 'GET' && str_ends_with($r->url(), '/v1/payments/987654321')
      && $r->hasHeader('Authorization', 'Bearer APP_USR-seller-token'));
  }

  public function test_webhook_with_invalid_signature_is_rejected(): void
  {
    Http::fake();

    $this->signedWebhook('987654321', secret: 'outro-segredo')->assertUnauthorized();
    $this->postJson(route('webhooks.payments', 'mercadopago') . '?data.id=1&type=payment', ['type' => 'payment'])->assertUnauthorized();

    $this->assertSame(0, WebhookEvent::query()->count());
    Http::assertNothingSent();
  }

  public function test_webhook_does_not_trust_payload_when_api_says_pending(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->fakeCharge();
    $order = $this->checkout($raffle, [3]);
    $this->forgetTenant();
    $this->remoteStatus = 'pending';

    // Corpo forjado dizendo "approved" não importa: o status vem da API.
    $this->signedWebhook('987654321', ['status' => 'approved'])->assertOk();

    $this->assertSame(OrderStatus::AwaitingPayment, $order->fresh()->status);
  }

  public function test_non_payment_notifications_are_acknowledged(): void
  {
    Http::fake();
    $ts = (string) time();
    $signature = hash_hmac('sha256', "id:abc;request-id:req-1;ts:{$ts};", self::SECRET);

    $this->postJson(route('webhooks.payments', 'mercadopago') . '?data.id=ABC&type=mp-connect',
      ['id' => 'n-1', 'type' => 'mp-connect', 'action' => 'application.authorized', 'data' => ['id' => 'ABC']],
      ['x-signature' => "ts={$ts},v1={$signature}", 'x-request-id' => 'req-1'],
    )->assertOk();

    $this->assertNotNull(WebhookEvent::query()->where('event_type', 'application.authorized')->value('processed_at'));
    Http::assertNothingSent();
  }

  // --- Estorno e conciliação ------------------------------------------------

  public function test_refund_uses_seller_token(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->fakeCharge();
    $order = $this->checkout($raffle, [4]);
    app(PaymentConfirmationService::class)->confirm($order->payment);
    Http::fake(['api.mercadopago.com/v1/payments/987654321/refunds' => Http::response(['id' => 42, 'status' => 'approved'], 201)]);

    app(RefundService::class)->refund($order->payment->fresh(), 'Teste de estorno');

    Http::assertSent(fn(HttpRequest $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v1/payments/987654321/refunds')
      && $r->hasHeader('Authorization', 'Bearer APP_USR-seller-token')
      && $r->hasHeader('X-Idempotency-Key', 'refund-' . $order->payment->id));
  }

  public function test_status_mapping_from_api(): void
  {
    [$tenant] = $this->mpOrganizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->fakeCharge();
    $payment = $this->checkout($raffle, [5])->payment;
    $gateway = app(PaymentGatewayManager::class)->driver('mercadopago');

    foreach (['approved' => PaymentStatus::Paid, 'refunded' => PaymentStatus::Refunded, 'cancelled' => PaymentStatus::Expired, 'in_process' => PaymentStatus::Pending] as $remote => $expected) {
      $this->remoteStatus = $remote;
      $this->assertSame($expected, $gateway->getCharge($payment)->status, $remote);
    }
  }

  // --- Conexão OAuth --------------------------------------------------------

  public function test_organizer_connects_mercado_pago_via_oauth(): void
  {
    [$tenant, $owner] = $this->organizer(['asaas_wallet_id' => null, 'payment_gateway' => null]);
    $this->forgetTenant();
    Http::fake(['api.mercadopago.com/oauth/token' => Http::response([
      'access_token' => 'APP_USR-connected', 'refresh_token' => 'TG-connected', 'user_id' => 777,
      'public_key' => 'APP_USR-pk', 'expires_in' => 15552000, 'live_mode' => false,
    ])]);

    $this->actingAs($owner)->get(route('painel.configuracoes.edit', ['aba' => 'recebimentos']))
      ->assertOk()->assertSee('Conectar com Mercado Pago')->assertSee('Asaas');

    $redirect = $this->get(route('painel.configuracoes.payments.connect', 'mercadopago'))->assertRedirect()->headers->get('Location');
    $this->assertStringStartsWith('https://auth.mercadopago.com.br/authorization?', $redirect);
    parse_str(parse_url($redirect, PHP_URL_QUERY), $query);
    $this->assertSame('client-id', $query['client_id']);
    $this->assertSame(route('painel.configuracoes.payments.callback', 'mercadopago'), $query['redirect_uri']);

    $this->get(route('painel.configuracoes.payments.callback', ['mercadopago', 'code' => 'TG-code', 'state' => $query['state']]))
      ->assertRedirect(route('painel.configuracoes.edit', ['aba' => 'recebimentos']))
      ->assertSessionHas('success');

    $tenant->refresh();
    $this->assertSame('mercadopago', $tenant->payment_gateway);
    $this->assertSame('777', $tenant->mp_user_id);
    $this->assertSame('APP_USR-connected', $tenant->mp_access_token);
    $this->assertTrue($tenant->canReceivePayments());
    Http::assertSent(fn(HttpRequest $r) => str_ends_with($r->url(), '/oauth/token') && $r['grant_type'] === 'authorization_code'
      && $r['code'] === 'TG-code' && $r['client_secret'] === 'client-secret' && !isset($r['test_token']));
    $this->assertDatabaseHas('audit_logs', ['action' => 'tenant.payments_configured']);
  }

  public function test_test_token_is_requested_only_when_enabled(): void
  {
    [$tenant] = $this->mpOrganizer(['mp_token_expires_at' => now()->addDay()]);
    config(['saas.mercadopago.test_token' => true]);
    Http::fake(['api.mercadopago.com/oauth/token' => Http::response(['access_token' => 'TEST-token', 'user_id' => 123456789, 'expires_in' => 15552000])]);

    app(PaymentGatewayManager::class)->driver('mercadopago')->refreshToken($tenant);

    Http::assertSent(fn(HttpRequest $r) => $r['test_token'] === 'true');
  }

  public function test_oauth_callback_with_invalid_state_is_rejected(): void
  {
    [$tenant, $owner] = $this->organizer(['asaas_wallet_id' => null, 'payment_gateway' => null]);
    $this->forgetTenant();
    Http::fake();

    $this->actingAs($owner)->get(route('painel.configuracoes.payments.connect', 'mercadopago'));
    $this->get(route('painel.configuracoes.payments.callback', ['mercadopago', 'code' => 'TG-code', 'state' => 'forjado']))
      ->assertSessionHas('error');

    $this->assertNull($tenant->fresh()->mp_user_id);
    Http::assertNothingSent();
  }

  public function test_operator_cannot_connect_and_disabled_gateway_returns_404(): void
  {
    [, $operator] = $this->organizer([], TenantRole::Operator);
    $this->forgetTenant();

    $this->actingAs($operator)->get(route('painel.configuracoes.payments.connect', 'mercadopago'))->assertForbidden();

    [, $owner] = $this->organizer();
    $this->forgetTenant();
    config(['saas.payment_gateways' => []]);
    $this->actingAs($owner)->get(route('painel.configuracoes.payments.connect', 'mercadopago'))->assertNotFound();
  }

  public function test_disconnect_is_blocked_with_active_raffle_and_allowed_otherwise(): void
  {
    [$tenant, $owner] = $this->mpOrganizer(['asaas_wallet_id' => 'wal_antiga']);
    $raffle = $this->publishedRaffle($tenant);
    $this->forgetTenant();

    $this->actingAs($owner)->delete(route('painel.configuracoes.payments.disconnect'))->assertSessionHas('error');
    $this->assertSame('mercadopago', $tenant->fresh()->payment_gateway);

    Raffle::query()->whereKey($raffle->id)->update(['status' => 'completed']);
    $this->delete(route('painel.configuracoes.payments.disconnect'))->assertSessionHas('success');

    $tenant->refresh();
    $this->assertNull($tenant->mp_access_token);
    $this->assertSame('asaas', $tenant->payment_gateway); // volta para a carteira Asaas anterior
    $this->assertDatabaseHas('audit_logs', ['action' => 'tenant.payments_disconnected']);
  }

  public function test_asaas_and_mercado_pago_organizers_coexist(): void
  {
    [$mpTenant] = $this->mpOrganizer();
    [$asaasTenant] = $this->organizer(['asaas_wallet_id' => 'wal_123', 'payment_gateway' => 'asaas']);
    $mpRaffle = $this->publishedRaffle($mpTenant);
    $asaasRaffle = $this->publishedRaffle($asaasTenant);
    $this->forgetTenant();
    Http::fake([
      'api.mercadopago.com/v1/payments' => Http::response(['id' => 1, 'status' => 'pending', 'point_of_interaction' => ['transaction_data' => ['qr_code' => 'pix-mp']]], 201),
      '*/customers' => Http::response(['id' => 'cus_1']),
      '*/payments/pay_1/pixQrCode' => Http::response(['encodedImage' => 'AAA', 'payload' => 'pix-asaas']),
      '*/v3/payments' => Http::response(['id' => 'pay_1', 'status' => 'PENDING']),
    ]);

    $this->assertSame('mercadopago', $this->checkout($mpRaffle, [1])->payment->gateway);
    $this->assertSame('asaas', $this->checkout($asaasRaffle, [1])->payment->gateway);
  }
}
