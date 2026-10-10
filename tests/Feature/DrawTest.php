<?php

namespace Tests\Feature;

use App\Domain\Draws\DrawService;
use App\Domain\Draws\Enums\DrawOutcome;
use App\Domain\Draws\Exceptions\DrawException;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\PaymentConfirmationService;
use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Enums\UnsoldRule;
use App\Domain\Raffles\Jobs\CloseSalesJob;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class DrawTest extends TestCase
{
  use RefreshDatabase;

  protected function closedRaffle(array $attributes, array $paidNumbers): array
  {
    [$tenant, $owner] = $this->organizer();
    $raffle = $this->publishedRaffle($tenant, $attributes + ['total_numbers' => 100]);
    foreach ($paidNumbers as $n) {
      $order = $this->checkout($raffle, [$n], buyer: ['cpf' => fake('pt_BR')->cpf(false)]);
      app(PaymentConfirmationService::class)->confirm($order->payment);
    }
    $raffle->forceFill(['sales_end_at' => now()->subMinute()])->save();
    CloseSalesJob::dispatchSync($raffle->id);

    return [$raffle->fresh(), $owner, $tenant];
  }

  protected function fakeFederal(string $firstPrize): void
  {
    Http::fake([
      '*portaldeloterias/api/federal/*' => Http::response([
        'numero' => 5900,
        'dataApuracao' => '07/10/2026',
        'listaDezenas' => [$firstPrize, '011111', '022222', '033333', '044444'],
      ]),
    ]);
  }

  public function test_federal_draw_picks_paid_number_and_is_reproducible(): void
  {
    Storage::fake('local');
    $this->fakeFederal('048342'); // 48342 mod 100 = 42
    [$raffle, $owner] = $this->closedRaffle([], [42, 10]);

    $this->actingAs($owner)->post(route('painel.rifas.draw.store', $raffle), ['concurso' => '5900', 'confirm' => '1'])->assertRedirect();

    $raffle->refresh();
    $draw = $raffle->draws()->first();
    $this->assertSame(RaffleStatus::Drawn, $raffle->status);
    $this->assertSame(DrawOutcome::Winner, $draw->outcome);
    $this->assertSame(42, $draw->drawn_number);
    $this->assertSame(42, app(DrawService::class)->recompute($draw));
    $this->assertTrue(app(DrawService::class)->verifyHash($draw));
    Storage::disk('local')->assertExists($draw->fresh()->minutes_pdf_path);
  }

  public function test_unsold_number_uses_next_paid_above_with_wraparound(): void
  {
    $this->fakeFederal('000095'); // 95 não vendido → próximo acima não existe → volta ao menor pago (3)
    [$raffle] = $this->closedRaffle(['unsold_rule' => UnsoldRule::NextPaidAbove], [3, 50]);
    $this->actingAs($this->admin());

    $draw = app(DrawService::class)->execute($raffle, ['concurso' => '5900']);

    $this->assertSame(95, $draw->computed_number);
    $this->assertSame(3, $draw->drawn_number);
  }

  public function test_unsold_number_with_redraw_rule_keeps_raffle_open_for_new_draw(): void
  {
    $this->fakeFederal('000077');
    [$raffle] = $this->closedRaffle(['unsold_rule' => UnsoldRule::Redraw], [1]);
    $this->actingAs($this->admin());

    $draw = app(DrawService::class)->execute($raffle, ['concurso' => '5900']);

    $this->assertSame(DrawOutcome::Redraw, $draw->outcome);
    $this->assertSame(RaffleStatus::SalesClosed, $raffle->fresh()->status);
  }

  public function test_unsold_number_with_void_rule_cancels_and_refunds(): void
  {
    $this->fakeFederal('000077');
    [$raffle] = $this->closedRaffle(['unsold_rule' => UnsoldRule::Void], [1, 2]);
    $this->actingAs($this->admin());

    $draw = app(DrawService::class)->execute($raffle, ['concurso' => '5900']);

    $this->assertSame(DrawOutcome::Void, $draw->outcome);
    $this->assertSame(RaffleStatus::Cancelled, $raffle->fresh()->status);
    $this->assertSame(2, $raffle->orders()->withoutGlobalScopes()->where('status', OrderStatus::Refunded)->count());
  }

  public function test_commit_reveal_draw_reveals_seed_matching_commit(): void
  {
    $this->fakeFederal('012345');
    [$raffle] = $this->closedRaffle(['draw_method' => DrawMethod::CommitReveal], range(0, 99));
    $this->actingAs($this->admin());

    $draw = app(DrawService::class)->execute($raffle, ['concurso' => '5900']);

    $this->assertSame($raffle->commit_hash, hash('sha256', $draw->seed_revealed));
    $expected = DrawService::modulo(DrawService::seedBase($draw->seed_revealed, '012345'), 100);
    $this->assertSame($expected, $draw->drawn_number);
    $this->assertSame($expected, app(DrawService::class)->recompute($draw));
  }

  public function test_manual_federal_result_requires_evidence(): void
  {
    Http::fake(['*' => Http::response([], 500)]);
    [$raffle] = $this->closedRaffle([], [5]);
    $this->actingAs($this->admin());

    $this->expectException(DrawException::class);
    app(DrawService::class)->execute($raffle, ['concurso' => '5900', 'premio' => '12305']);
  }

  public function test_draw_requires_sales_closed_and_record_is_immutable(): void
  {
    $this->fakeFederal('000005');
    [$tenant] = $this->organizer();
    $open = $this->publishedRaffle($tenant);
    $this->actingAs($this->admin());

    try {
      app(DrawService::class)->execute($open, ['concurso' => '1']);
      $this->fail('Sorteio com vendas abertas deveria falhar');
    } catch (DrawException) {
    }

    [$raffle] = $this->closedRaffle([], [5]);
    $draw = app(DrawService::class)->execute($raffle, ['concurso' => '5900']);

    $this->expectException(LogicException::class);
    $draw->forceFill(['drawn_number' => 6])->save();
  }

  public function test_public_proof_page_shows_result(): void
  {
    $this->fakeFederal('000042');
    [$raffle, , $tenant] = $this->closedRaffle([], [42]);
    $this->actingAs($this->admin());
    app(DrawService::class)->execute($raffle, ['concurso' => '5900']);
    auth()->logout();
    $this->forgetTenant();

    $this->get(route('public.draw', [$tenant->slug, $raffle->slug]))
      ->assertOk()->assertSee('Número contemplado')->assertSee('recalculado e confere');
  }
}
