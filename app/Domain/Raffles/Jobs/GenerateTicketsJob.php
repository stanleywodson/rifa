<?php

namespace App\Domain\Raffles\Jobs;

use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleStateMachine;
use App\Domain\Tickets\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Cria uma linha por número (em lotes) e publica a rifa ao final.
 * Idempotente: pode ser reexecutado após falha parcial.
 */
class GenerateTicketsJob implements ShouldQueue
{
  use Queueable;

  public const CHUNK = 5000;

  public int $timeout = 600;

  public function __construct(public int $raffleId) {}

  public function handle(RaffleStateMachine $states): void
  {
    $raffle = Raffle::query()->withoutGlobalScopes()->findOrFail($this->raffleId);
    if ($raffle->tickets_generated_at) {
      return;
    }

    $start = $raffle->number_start;
    $end = $raffle->lastNumber();
    $existing = (int) Ticket::query()->where('raffle_id', $raffle->id)->max('number');
    $from = Ticket::query()->where('raffle_id', $raffle->id)->exists() ? $existing + 1 : $start;

    for ($n = $from; $n <= $end; $n += self::CHUNK) {
      $rows = [];
      $last = min($n + self::CHUNK - 1, $end);
      for ($i = $n; $i <= $last; $i++) {
        $rows[] = ['raffle_id' => $raffle->id, 'number' => $i, 'status' => 'free'];
      }
      Ticket::insertOrIgnore($rows);
    }

    $raffle->forceFill(['tickets_generated_at' => now()])->save();

    if ($raffle->status === RaffleStatus::PendingReview) {
      $states->transition($raffle, RaffleStatus::Published, ['published_at' => now()]);
    }
  }
}
