<?php

namespace App\Domain\Draws\Enums;

use App\Domain\Support\HasBadge;

enum DrawOutcome: string
{
  use HasBadge;

  case Winner = 'winner';
  case Redraw = 'redraw';
  case Void = 'void';

  public function label(): string
  {
    return match ($this) {
      self::Winner => 'Ganhador definido',
      self::Redraw => 'Novo sorteio necessário',
      self::Void => 'Rifa anulada',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Winner => 'success',
      self::Redraw => 'warning',
      self::Void => 'danger',
    };
  }
}
