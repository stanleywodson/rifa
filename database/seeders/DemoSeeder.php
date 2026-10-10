<?php

namespace Database\Seeders;

use App\Domain\Draws\DrawService;
use App\Domain\Orders\CheckoutService;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Jobs\CloseSalesJob;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleReviewService;
use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Models\Plan;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tenants\TenantContext;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Dados de desenvolvimento: admin, 2 organizadores e rifas em vários estados.
 * Senha de todos os usuários: password
 */
class DemoSeeder extends Seeder
{
  public function run(): void
  {
    Mail::fake();
    config(['saas.payment_gateway' => 'fake', 'queue.default' => 'sync']);
    Storage::disk('local')->put('seed/autorizacao.pdf', '%PDF-1.4 documento de exemplo');

    $admin = User::factory()->admin()->create(['name' => 'Admin da Plataforma', 'email' => 'admin@rifa.local']);
    auth()->setUser($admin);

    $owner = User::factory()->create(['name' => 'Maria Organizadora', 'email' => 'organizador@rifa.local', 'phone' => '11988887777']);
    $operator = User::factory()->create(['name' => 'João Operador', 'email' => 'operador@rifa.local']);

    $church = Tenant::factory()->create([
      'name' => 'Paróquia São José',
      'slug' => 'paroquia-sao-jose',
      'plan_id' => Plan::query()->where('code', 'pro')->value('id'),
      'asaas_wallet_id' => 'fake_wallet_demo',
      'payment_gateway' => 'fake',
      'settings' => ['about' => 'Rifas beneficentes para a reforma da igreja matriz.'],
    ]);
    $church->users()->attach($owner, ['role' => TenantRole::Owner->value]);
    $church->users()->attach($operator, ['role' => TenantRole::Operator->value]);

    $ong = Tenant::factory()->unverified()->withOwner(User::factory()->create(['email' => 'ong@rifa.local', 'name' => 'Carlos ONG']))->create([
      'name' => 'ONG Patinhas',
      'slug' => 'ong-patinhas',
      'plan_id' => Plan::query()->where('code', 'starter')->value('id'),
    ]);

    // Rascunho e em análise
    Raffle::factory()->for($church)->create(['title' => 'Rifa da Bicicleta Elétrica', 'slug' => 'bicicleta-eletrica']);
    $review = Raffle::factory()->for($church)->status(RaffleStatus::PendingReview)->create([
      'title' => 'Rifa do Notebook', 'slug' => 'notebook', 'submitted_at' => now()->subHours(3), 'total_numbers' => 500,
    ]);
    $review->prizes()->create(['position' => 1, 'title' => 'Notebook i7 16GB', 'estimated_value_cents' => 450000]);
    Raffle::factory()->for($ong)->create(['title' => 'Rifa Solidária Patinhas', 'slug' => 'rifa-solidaria']);

    // Publicada com vendas
    $moto = $this->publish(Raffle::factory()->for($church)->status(RaffleStatus::PendingReview)->create([
      'title' => 'Rifa da Moto 0km', 'slug' => 'moto-0km', 'total_numbers' => 1000, 'price_cents' => 500,
      'summary' => 'Concorra a uma Honda CG 160 0km e ajude na reforma da igreja.',
    ]), 'Honda CG 160 Start 0km', 1890000);
    $this->sell($moto, 25);

    // Vendas encerradas (pronta para sorteio, commit-reveal)
    $tv = $this->publish(Raffle::factory()->for($church)->method(DrawMethod::CommitReveal)->status(RaffleStatus::PendingReview)->create([
      'title' => 'Rifa da Smart TV 65"', 'slug' => 'smart-tv', 'total_numbers' => 100, 'price_cents' => 2000,
    ]), 'Smart TV 65" 4K', 420000);
    $this->sell($tv, 12, payAll: true);
    $this->closeSales($tv);

    // Sorteada (manual)
    $basket = $this->publish(Raffle::factory()->for($church)->method(DrawMethod::Manual)->status(RaffleStatus::PendingReview)->create([
      'title' => 'Rifa da Cesta de Natal', 'slug' => 'cesta-de-natal', 'total_numbers' => 50, 'price_cents' => 1000,
    ]), 'Cesta de Natal Premium', 60000);
    $this->sell($basket, 8, payAll: true);
    $this->closeSales($basket);
    $winner = $basket->tickets()->where('status', 'paid')->value('number');
    app(DrawService::class)->execute($basket->fresh(), ['number' => $winner, 'evidence_url' => 'https://www.youtube.com/watch?v=exemplo']);

    auth()->logout();
    app(TenantContext::class)->forget();
  }

  protected function publish(Raffle $raffle, string $prize, int $value): Raffle
  {
    $raffle->prizes()->create(['position' => 1, 'title' => $prize, 'estimated_value_cents' => $value]);
    $raffle->forceFill(['submitted_at' => now()])->save();
    app(RaffleReviewService::class)->approve($raffle);

    return $raffle->fresh();
  }

  protected function sell(Raffle $raffle, int $orders, bool $payAll = false): void
  {
    $checkout = app(CheckoutService::class);
    $confirm = app(PaymentConfirmationService::class);

    for ($i = 0; $i < $orders; $i++) {
      $order = $checkout->checkout($raffle->fresh('tenant'), [
        'name' => fake()->name(),
        'cpf' => fake()->cpf(false),
        'email' => fake()->safeEmail(),
        'phone' => '119' . fake()->numerify('########'),
        'birthdate' => fake()->dateTimeBetween('-60 years', '-19 years')->format('Y-m-d'),
      ], [], random_int(1, 5));

      // ~80% pagos, restante aguardando pagamento
      if ($payAll || $i % 5 !== 4) {
        $confirm->confirm($order->payment);
      }
    }
  }

  protected function closeSales(Raffle $raffle): void
  {
    $raffle->forceFill(['sales_end_at' => now()->subMinute(), 'draw_at' => now()->addHour()])->save();
    CloseSalesJob::dispatchSync($raffle->id);
  }
}
