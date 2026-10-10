<?php

namespace Tests\Feature;

use App\Domain\Payments\PaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: todas as telas do painel e do admin renderizam.
 */
class PanelPagesTest extends TestCase
{
  use RefreshDatabase;

  public function test_organizer_pages_render(): void
  {
    $this->seedPlans();
    [$tenant, $owner] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $order = $this->checkout($raffle, [1]);
    app(PaymentConfirmationService::class)->confirm($order->payment);
    $this->forgetTenant();

    $this->actingAs($owner);
    foreach ([
      route('painel.dashboard'),
      route('painel.rifas.index'),
      route('painel.rifas.create'),
      route('painel.rifas.show', $raffle),
      route('painel.pedidos.index'),
      route('painel.pedidos.show', $order),
      route('painel.compradores.index'),
      route('painel.compradores.show', $order->customer_id),
      route('painel.configuracoes.edit'),
      route('conta.edit'),
    ] as $url) {
      $this->get($url)->assertOk();
    }

    $this->getJson(route('painel.pedidos.data', ['draw' => 1]))->assertOk()->assertJsonPath('recordsTotal', 1);
    $this->getJson(route('painel.compradores.data', ['draw' => 1]))->assertOk();
    $this->get(route('painel.rifas.export.csv', $raffle))->assertOk();
    $this->get(route('painel.rifas.export.pdf', $raffle))->assertOk();
  }

  public function test_admin_pages_render(): void
  {
    $this->seedPlans();
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $this->forgetTenant();

    $this->actingAs($this->admin());
    foreach ([
      route('admin.dashboard'),
      route('admin.rifas.index'),
      route('admin.rifas.show', $raffle),
      route('admin.organizadores.index'),
      route('admin.organizadores.show', $tenant->id),
      route('admin.planos.index'),
      route('admin.financeiro.index'),
      route('admin.auditoria.index'),
    ] as $url) {
      $this->get($url)->assertOk();
    }
    foreach (['admin.rifas.data', 'admin.organizadores.data', 'admin.financeiro.data', 'admin.auditoria.data'] as $name) {
      $this->getJson(route($name, ['draw' => 1]))->assertOk();
    }
  }
}
