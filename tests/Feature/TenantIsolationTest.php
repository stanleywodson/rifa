<?php

namespace Tests\Feature;

use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\TenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
  use RefreshDatabase;

  public function test_organizer_cannot_access_another_tenants_raffle_by_id(): void
  {
    [$tenantA, $userA] = $this->organizer();
    [$tenantB] = $this->organizer();
    $raffleB = Raffle::factory()->for($tenantB)->create();

    $this->actingAs($userA)->get(route('painel.rifas.show', $raffleB->id))->assertNotFound();
    $this->actingAs($userA)->get(route('painel.rifas.edit', $raffleB->id))->assertNotFound();
    $this->actingAs($userA)->put(route('painel.rifas.update', $raffleB->id), ['title' => 'x'])->assertNotFound();
  }

  public function test_listing_only_returns_current_tenant_data(): void
  {
    [$tenantA, $userA] = $this->organizer();
    [$tenantB] = $this->organizer();
    Raffle::factory()->for($tenantA)->create(['title' => 'Rifa do A']);
    Raffle::factory()->for($tenantB)->create(['title' => 'Rifa do B']);

    $response = $this->actingAs($userA)->getJson(route('painel.rifas.data', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonPath('recordsTotal', 1);
    $this->assertStringContainsString('Rifa do A', $response->json('data.0.raffle'));
  }

  public function test_session_tenant_id_cannot_be_forged(): void
  {
    [$tenantA, $userA] = $this->organizer();
    [$tenantB] = $this->organizer();

    $this->actingAs($userA)->withSession(['tenant_id' => $tenantB->id])->get(route('painel.dashboard'))->assertOk();
    $this->assertSame($tenantA->id, session('tenant_id'));

    $this->actingAs($userA)->post(route('painel.switch', $tenantB->id))->assertForbidden();
  }

  public function test_operator_cannot_create_or_edit_raffles(): void
  {
    [$tenant, $operator] = $this->organizer([], TenantRole::Operator);
    $raffle = Raffle::factory()->for($tenant)->create();

    $this->actingAs($operator)->get(route('painel.rifas.create'))->assertForbidden();
    $this->actingAs($operator)->get(route('painel.rifas.edit', $raffle))->assertForbidden();
    $this->actingAs($operator)->get(route('painel.rifas.show', $raffle))->assertOk();
  }

  public function test_admin_area_requires_platform_admin(): void
  {
    [, $user] = $this->organizer();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();
  }

  public function test_admin_area_requires_two_factor_when_enforced(): void
  {
    config(['saas.require_admin_2fa' => true]);
    $admin = $this->admin();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('conta.edit', ['aba' => 'seguranca']));

    $admin->forceFill(['two_factor_secret' => encrypt('x'), 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
  }

  public function test_suspended_tenant_panel_is_read_only(): void
  {
    [$tenant, $user] = $this->organizer(['status' => 'suspended']);

    $this->actingAs($user)->get(route('painel.dashboard'))->assertOk();
    $this->actingAs($user)->post(route('painel.rifas.store'), [])->assertForbidden();
  }
}
