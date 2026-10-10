<?php

namespace Tests\Feature;

use App\Domain\Orders\Exceptions\CheckoutUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentAccountTest extends TestCase
{
  use RefreshDatabase;

  public function test_wallet_created_in_fake_mode_is_not_used_with_asaas(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    config(['saas.payment_gateway' => 'asaas', 'saas.asaas.api_key' => 'test']);
    Http::fake();

    $this->assertFalse($tenant->fresh()->canReceivePayments());

    try {
      $this->checkout($raffle, [1]);
      $this->fail('Checkout deveria ser bloqueado');
    } catch (CheckoutUnavailable $e) {
      $this->assertStringContainsString('não está habilitado para receber', $e->getMessage());
    }
    Http::assertNothingSent();
  }

  public function test_asaas_charge_sends_split_to_asaas_wallet(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $tenant->forceFill(['asaas_wallet_id' => 'wal_123', 'payment_gateway' => 'asaas'])->save();
    config(['saas.payment_gateway' => 'asaas', 'saas.asaas.api_key' => 'test']);
    Http::fake([
      '*/customers' => Http::response(['id' => 'cus_1']),
      '*/payments' => Http::response(['id' => 'pay_1', 'status' => 'PENDING']),
      '*/payments/pay_1/pixQrCode' => Http::response(['encodedImage' => 'AAA', 'payload' => '000201']),
    ]);

    $order = $this->checkout($raffle, [1]);

    $this->assertSame('pay_1', $order->payment->gateway_id);
    Http::assertSent(fn($r) => str_ends_with($r->url(), '/payments') && $r['split'][0]['walletId'] === 'wal_123'
      && $r['split'][0]['percentualValue'] == 100 - $tenant->feePercent());
  }

  public function test_raffle_page_and_panel_warn_when_payments_are_blocked(): void
  {
    [$tenant, $owner] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->forgetTenant();
    config(['saas.payment_gateway' => 'asaas']);

    $this->assertFalse($raffle->fresh()->isOnSale());
    $this->get(route('public.raffle', [$tenant->slug, $raffle->slug]))->assertOk()->assertSee('temporariamente indisponíveis');
    $this->get(route('public.checkout', [$tenant->slug, $raffle->slug, 'numeros' => '1']))->assertRedirect();
    $this->actingAs($owner)->get(route('painel.dashboard'))->assertSee('Configurar recebimentos');
  }

  public function test_charge_is_deleted_when_pix_qr_code_fails(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $tenant->forceFill(['asaas_wallet_id' => 'wal_123', 'payment_gateway' => 'asaas'])->save();
    config(['saas.payment_gateway' => 'asaas', 'saas.asaas.api_key' => 'test']);
    Http::fake([
      '*/customers' => Http::response(['id' => 'cus_1']),
      '*/payments/pay_1/pixQrCode' => Http::response(['errors' => [['description' => 'Você não possui uma chave Pix cadastrada']]], 400),
      '*/payments/pay_1' => Http::response(['deleted' => true]),
      '*/payments' => Http::response(['id' => 'pay_1', 'status' => 'PENDING']),
    ]);

    try {
      $this->checkout($raffle, [1]);
      $this->fail('Checkout deveria falhar');
    } catch (CheckoutUnavailable) {
    }

    Http::assertSent(fn($r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/payments/pay_1'));
    $this->assertSame('free', $raffle->tickets()->where('number', 1)->first()->status->value);
  }
}

