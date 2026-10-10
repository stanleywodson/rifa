<?php

namespace Tests;

use App\Domain\Orders\CheckoutService;
use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleReviewService;
use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tenants\TenantContext;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Mail::fake();
  }

  protected function seedPlans(): void
  {
    $this->seed(PlanSeeder::class);
  }

  /**
   * @return array{0: Tenant, 1: User}
   */
  protected function organizer(array $tenantAttributes = [], TenantRole $role = TenantRole::Owner): array
  {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create($tenantAttributes + ['asaas_wallet_id' => 'fake_wallet', 'payment_gateway' => 'fake']);
    $tenant->users()->attach($user, ['role' => $role->value]);

    return [$tenant, $user];
  }

  protected function admin(): User
  {
    return User::factory()->admin()->create();
  }

  /**
   * Rifa aprovada e publicada (números gerados).
   */
  protected function publishedRaffle(Tenant $tenant, array $attributes = []): Raffle
  {
    $raffle = Raffle::factory()->for($tenant)->status(RaffleStatus::PendingReview)->create($attributes + ['submitted_at' => now()]);
    $raffle->prizes()->create(['position' => 1, 'title' => 'Prêmio de teste']);

    $this->actingAs($this->admin());
    app(RaffleReviewService::class)->approve($raffle);
    auth()->logout();

    return $raffle->fresh();
  }

  protected function buyer(array $overrides = []): array
  {
    return $overrides + [
      'name' => 'Comprador Teste',
      'cpf' => fake('pt_BR')->cpf(false),
      'email' => 'comprador@example.com',
      'phone' => '11999998888',
      'birthdate' => '1990-01-01',
    ];
  }

  protected function checkout(Raffle $raffle, array $numbers = [], int $random = 0, array $buyer = []): Order
  {
    return app(CheckoutService::class)->checkout($raffle->fresh('tenant'), $this->buyer($buyer), $numbers, $random);
  }

  protected function forgetTenant(): void
  {
    app(TenantContext::class)->forget();
  }
}
