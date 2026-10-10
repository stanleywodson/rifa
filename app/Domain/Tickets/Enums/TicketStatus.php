<?php

namespace App\Domain\Tickets\Enums;

use App\Domain\Support\HasBadge;

enum TicketStatus: string
{
  use HasBadge;

  case Free = 'free';
  case Reserved = 'reserved';
  case Paid = 'paid';

  public function label(): string
  {
    return match ($this) {
      self::Free => 'Livre',
      self::Reserved => 'Reservado',
      self::Paid => 'Pago',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Free => 'secondary',
      self::Reserved => 'warning',
      self::Paid => 'success',
    };
  }
}
