<?php

namespace App\Domain\Tenants\Enums;

use App\Domain\Support\HasBadge;

enum TenantRole: string
{
  use HasBadge;

  case Owner = 'owner';
  case Operator = 'operator';

  public function label(): string
  {
    return match ($this) {
      self::Owner => 'Proprietário',
      self::Operator => 'Operador',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Owner => 'primary',
      self::Operator => 'info',
    };
  }
}
