<?php

namespace App\Domain\Raffles\Enums;

use App\Domain\Support\HasBadge;

enum DrawMethod: string
{
  use HasBadge;

  case Federal = 'federal';
  case CommitReveal = 'commit_reveal';
  case Manual = 'manual';

  public function label(): string
  {
    return match ($this) {
      self::Federal => 'Loteria Federal',
      self::CommitReveal => 'Semente auditável (commit-reveal)',
      self::Manual => 'Sorteio manual ao vivo',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Federal => 'primary',
      self::CommitReveal => 'info',
      self::Manual => 'warning',
    };
  }
}
