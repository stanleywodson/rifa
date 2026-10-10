<?php

namespace App\Domain\Raffles\Enums;

use App\Domain\Support\HasBadge;

enum UnsoldRule: string
{
  use HasBadge;

  case NextPaidAbove = 'next_paid_above';
  case Redraw = 'redraw';
  case Void = 'void';

  public function label(): string
  {
    return match ($this) {
      self::NextPaidAbove => 'Próximo número pago acima',
      self::Redraw => 'Novo sorteio (próximo concurso)',
      self::Void => 'Anular rifa e reembolsar',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::NextPaidAbove => 'primary',
      self::Redraw => 'info',
      self::Void => 'danger',
    };
  }
}
