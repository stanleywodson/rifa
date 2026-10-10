<?php

namespace App\Domain\Tenants\Enums;

use App\Domain\Support\HasBadge;

enum TenantStatus: string
{
  use HasBadge;

  case Active = 'active';
  case Suspended = 'suspended';

  public function label(): string
  {
    return match ($this) {
      self::Active => 'Ativo',
      self::Suspended => 'Suspenso',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Active => 'success',
      self::Suspended => 'danger',
    };
  }
}
