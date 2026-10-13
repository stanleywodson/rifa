<?php

namespace Tests\Feature;

use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
  use RefreshDatabase;

  public function test_public_pages_render(): void
  {
    $this->seedPlans();
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);

    $this->get(route('home'))->assertOk()->assertSee($raffle->title);
    $this->get(route('public.tenant', $tenant->slug))->assertOk()->assertSee($raffle->title);
    $this->get(route('public.raffle', [$tenant->slug, $raffle->slug]))->assertOk()
      ->assertSee('Escolha seus números')->assertSee('og:title', false);
    $this->get(route('public.checkout', [$tenant->slug, $raffle->slug, 'numeros' => '12,3,7']))->assertOk()->assertSee('Gerar Pix')
      ->assertSee('name="numbers" value="3,7,12"', false);
    $this->get(route('public.my-tickets'))->assertOk();
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
  }

  public function test_grid_returns_compact_statuses(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant, ['total_numbers' => 30]);
    $this->checkout($raffle, [2]);
    $this->forgetTenant();

    $this->getJson(route('public.raffle.grid', [$tenant->slug, $raffle->slug]))
      ->assertOk()
      ->assertJsonPath('from', 0)
      ->assertJsonPath('to', 29)
      ->assertJsonPath('statuses', '001' . str_repeat('0', 27));
  }

  public function test_draft_raffles_and_suspended_tenants_are_hidden(): void
  {
    [$tenant] = $this->organizer();
    $draft = Raffle::factory()->for($tenant)->create();
    $published = $this->publishedRaffle($tenant);
    $this->forgetTenant();

    $this->get(route('public.raffle', [$tenant->slug, $draft->slug]))->assertNotFound();

    $tenant->forceFill(['status' => TenantStatus::Suspended])->save();
    $this->get(route('public.raffle', [$tenant->slug, $published->slug]))->assertNotFound();
    $this->get(route('public.tenant', $tenant->slug))->assertNotFound();
  }

  public function test_raffle_slug_is_scoped_to_its_tenant(): void
  {
    [$tenantA] = $this->organizer();
    [$tenantB] = $this->organizer();
    $raffle = $this->publishedRaffle($tenantA);
    $this->forgetTenant();

    $this->get(route('public.raffle', [$tenantB->slug, $raffle->slug]))->assertNotFound();
  }

  public function test_my_tickets_requires_matching_document_and_code(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);
    $order = $this->checkout($raffle, [4], buyer: ['cpf' => '52998224725']);
    $this->forgetTenant();

    $this->post(route('public.my-tickets.lookup'), ['document' => '111.444.777-35', 'code' => $order->code])->assertSessionHas('error');
    $this->post(route('public.my-tickets.lookup'), ['document' => '529.982.247-25', 'code' => strtoupper($order->code)])
      ->assertOk()->assertSee($raffle->title);
  }

  public function test_registration_creates_owner_and_tenant(): void
  {
    $this->seedPlans();

    $this->post(route('register'), [
      'name' => 'Ana Souza',
      'email' => 'ana@example.com',
      'phone' => '(11) 98888-7777',
      'password' => 'senha-forte-123',
      'password_confirmation' => 'senha-forte-123',
      'organization' => 'Associação Amigos',
      'slug' => 'associacao-amigos',
      'document' => '11.222.333/0001-81',
      'terms' => '1',
      'adult' => '1',
    ])->assertRedirect();

    $user = \App\Models\User::where('email', 'ana@example.com')->firstOrFail();
    $this->assertTrue($user->isOwnerOf($user->tenants->first()));
    $this->assertSame('starter', $user->tenants->first()->plan->code);
  }

  public function test_reserved_slugs_are_rejected_on_registration(): void
  {
    $this->post(route('register'), [
      'name' => 'X', 'email' => 'x@example.com', 'phone' => '11988887777', 'password' => 'senha-forte-123',
      'password_confirmation' => 'senha-forte-123', 'organization' => 'Admin', 'slug' => 'admin',
      'document' => '52998224725', 'terms' => '1', 'adult' => '1',
    ])->assertSessionHasErrors('slug');
  }
}
