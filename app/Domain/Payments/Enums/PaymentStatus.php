<?php

namespace App\Domain\Payments\Enums;

use App\Domain\Support\HasBadge;

enum PaymentStatus: string
{
  use HasBadge;

  case Pending = 'pending';
  case Paid = 'paid';
  case Expired = 'expired';
  case Refunded = 'refunded';
  case Failed = 'failed';

  public function label(): string
  {
    return match ($this) {
      self::Pending => 'Pendente',
      self::Paid => 'Pago',
      self::Expired => 'Expirado',
      self::Refunded => 'Estornado',
      self::Failed => 'Falhou',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Pending => 'warning',
      self::Paid => 'success',
      self::Expired => 'secondary',
      self::Refunded => 'info',
      self::Failed => 'danger',
    };
  }
}
