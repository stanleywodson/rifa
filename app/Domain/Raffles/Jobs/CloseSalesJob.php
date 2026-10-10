<?php

namespace App\Domain\Raffles\Jobs;

use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleStateMachine;
use App\Domain\Tickets\Enums\TicketStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Encerra as vendas quando a data chega ou todos os números foram pagos.
 * Sem raffleId: varre todas as rifas publicadas (agendado a cada minuto).
 */
class CloseSalesJob implements ShouldQueue
{
  use Queueable;

  public function __construct(public ?int $raffleId = null) {}

  public function handle(RaffleStateMachine $states): void
  {
    Raffle::query()->withoutGlobalScopes()
      ->where('status', RaffleStatus::Published)
      ->when($this->raffleId, fn($q) => $q->whereKey($this->raffleId))
      ->each(function (Raffle $raffle) use ($states) {
        $soldOut = $raffle->tickets()->where('status', TicketStatus::Paid)->count() >= $raffle->total_numbers;
        $ended = $raffle->sales_end_at && $raffle->sales_end_at->isPast();

        if ($soldOut || $ended) {
          $states->transition($raffle, RaffleStatus::SalesClosed, ['sales_closed_at' => now()], $soldOut ? 'Todos os números vendidos' : 'Prazo de vendas encerrado');
        }
      });
  }
}
