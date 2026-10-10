<?php

namespace App\Domain\Tenants\Enums;

use App\Domain\Support\HasBadge;

enum KycStatus: string
{
  use HasBadge;

  case Pending = 'pending';
  case Submitted = 'submitted';
  case Approved = 'approved';
  case Rejected = 'rejected';

  public function label(): string
  {
    return match ($this) {
      self::Pending => 'Pendente',
      self::Submitted => 'Em análise',
      self::Approved => 'Verificado',
      self::Rejected => 'Reprovado',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Pending => 'secondary',
      self::Submitted => 'warning',
      self::Approved => 'success',
      self::Rejected => 'danger',
    };
  }
}
