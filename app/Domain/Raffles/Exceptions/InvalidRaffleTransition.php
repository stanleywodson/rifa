<?php

namespace App\Domain\Raffles\Exceptions;

use App\Domain\Raffles\Enums\RaffleStatus;
use DomainException;

class InvalidRaffleTransition extends DomainException
{
  public static function make(RaffleStatus $from, RaffleStatus $to): self
  {
    return new self("Transição inválida: {$from->label()} → {$to->label()}.");
  }
}
