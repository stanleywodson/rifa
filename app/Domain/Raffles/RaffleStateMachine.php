<?php

namespace App\Domain\Raffles;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Raffles\Enums\RaffleStatus as S;
use App\Domain\Raffles\Exceptions\InvalidRaffleTransition;
use App\Domain\Raffles\Models\Raffle;

/**
 * Único ponto autorizado a mudar o status de uma rifa.
 */
class RaffleStateMachine
{
  public const TRANSITIONS = [
    'draft' => [S::PendingReview, S::Cancelled],
    'changes_requested' => [S::PendingReview, S::Draft, S::Cancelled],
    'pending_review' => [S::Published, S::ChangesRequested, S::Rejected],
    'rejected' => [],
    'published' => [S::SalesClosed, S::Cancelled],
    'sales_closed' => [S::Drawn, S::Cancelled],
    'drawn' => [S::Completed],
    'completed' => [],
    'cancelled' => [],
  ];

  public function __construct(protected AuditLogger $audit) {}

  public function can(Raffle $raffle, S $to): bool
  {
    return in_array($to, self::TRANSITIONS[$raffle->status->value] ?? [], true);
  }

  /**
   * @param  array<string, mixed>  $attributes  atributos extras gravados junto da transição
   */
  public function transition(Raffle $raffle, S $to, array $attributes = [], ?string $note = null): Raffle
  {
    $from = $raffle->status;
    if (!$this->can($raffle, $to)) {
      throw InvalidRaffleTransition::make($from, $to);
    }

    $raffle->forceFill(array_merge($attributes, ['status' => $to]))->save();

    $this->audit->log('raffle.' . $to->value, $raffle, ['status' => $from->value], array_filter([
      'status' => $to->value,
      'note' => $note,
    ]));

    return $raffle;
  }
}
