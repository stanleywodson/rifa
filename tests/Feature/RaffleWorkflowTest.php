<?php

namespace Tests\Feature;

use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\KycStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class RaffleWorkflowTest extends TestCase
{
  use RefreshDatabase;

  protected function payload(array $overrides = []): array
  {
    return $overrides + [
      'title' => 'Rifa da Bicicleta',
      'summary' => 'Uma bicicleta nova',
      'description' => '<p>Descrição <script>alert(1)</script></p>',
      'prize_title' => 'Bicicleta aro 29',
      'prize_value' => '2.500,00',
      'total_numbers' => 100,
      'number_start' => 0,
      'price' => '10,00',
      'min_numbers_per_order' => 1,
      'sales_end_at' => now()->addDays(5)->format('Y-m-d H:i'),
      'draw_at' => now()->addDays(6)->format('Y-m-d H:i'),
      'draw_method' => 'federal',
      'unsold_rule' => 'next_paid_above',
      'regulation' => str_repeat('Regulamento da rifa com regras claras. ', 3),
      'authorization_number' => 'SPA 123/2026',
    ];
  }

  public function test_full_flow_draft_submit_approve_publish(): void
  {
    Storage::fake('public');
    Storage::fake('local');
    [$tenant, $owner] = $this->organizer();

    $this->actingAs($owner)->post(route('painel.rifas.store'), $this->payload([
      'authorization_doc' => UploadedFile::fake()->create('autorizacao.pdf', 100, 'application/pdf'),
      'images' => [UploadedFile::fake()->image('premio.jpg')],
    ]))->assertRedirect();

    $raffle = Raffle::query()->withoutGlobalScopes()->firstOrFail();
    $this->assertSame(RaffleStatus::Draft, $raffle->status);
    $this->assertSame(1000, $raffle->price_cents);
    $this->assertSame(250000, $raffle->prize->estimated_value_cents);
    $this->assertStringNotContainsString('<script', $raffle->description);

    $this->actingAs($owner)->post(route('painel.rifas.submit', $raffle))->assertSessionHasNoErrors();
    $this->assertSame(RaffleStatus::PendingReview, $raffle->fresh()->status);

    // Organizador não edita durante a análise
    $this->actingAs($owner)->get(route('painel.rifas.edit', $raffle))->assertForbidden();

    $this->forgetTenant();
    $this->actingAs($this->admin())->post(route('admin.rifas.approve', $raffle))->assertRedirect();

    $raffle->refresh();
    $this->assertSame(RaffleStatus::Published, $raffle->status);
    $this->assertSame(100, $raffle->tickets()->count());
    $this->assertSame(hash('sha256', $raffle->seed_encrypted), $raffle->commit_hash);
  }

  public function test_submit_is_blocked_with_pending_requirements(): void
  {
    [$tenant, $owner] = $this->organizer(['kyc_status' => KycStatus::Pending]);
    $raffle = Raffle::factory()->for($tenant)->create(['authorization_doc_path' => null]);

    $this->actingAs($owner)->post(route('painel.rifas.submit', $raffle))->assertSessionHasErrors('raffle');
    $this->assertSame(RaffleStatus::Draft, $raffle->fresh()->status);
  }

  public function test_admin_cannot_approve_raffle_of_unverified_tenant(): void
  {
    [$tenant] = $this->organizer(['kyc_status' => KycStatus::Submitted]);
    $raffle = Raffle::factory()->for($tenant)->status(RaffleStatus::PendingReview)->create();

    $this->actingAs($this->admin())->post(route('admin.rifas.approve', $raffle))->assertSessionHasErrors('raffle');
    $this->assertNull($raffle->fresh()->approved_at);
  }

  public function test_rules_are_frozen_after_publication(): void
  {
    [$tenant] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant);

    $this->expectException(LogicException::class);
    $raffle->forceFill(['price_cents' => 1])->save();
  }

  public function test_request_changes_allows_editing_again(): void
  {
    [$tenant, $owner] = $this->organizer();
    $raffle = Raffle::factory()->for($tenant)->status(RaffleStatus::PendingReview)->create();

    $this->actingAs($this->admin())->post(route('admin.rifas.changes', $raffle), ['notes' => 'Envie o documento legível.'])->assertRedirect();
    $this->assertSame(RaffleStatus::ChangesRequested, $raffle->fresh()->status);

    $this->forgetTenant();
    $this->actingAs($owner)->get(route('painel.rifas.edit', $raffle))->assertOk();
  }

  public function test_plan_limits_are_enforced_on_submit(): void
  {
    $this->seedPlans();
    [$tenant, $owner] = $this->organizer(['plan_id' => \App\Domain\Tenants\Models\Plan::where('code', 'starter')->value('id')]);
    $this->publishedRaffle($tenant);
    $second = Raffle::factory()->for($tenant)->create();
    $second->prizes()->create(['position' => 1, 'title' => 'x']);
    $second->media()->create(['path' => 'x.jpg']);

    $this->actingAs($owner)->post(route('painel.rifas.submit', $second))->assertSessionHasErrors('raffle');
  }
}
