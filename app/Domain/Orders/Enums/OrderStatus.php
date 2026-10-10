<?php

namespace App\Domain\Orders\Enums;

use App\Domain\Support\HasBadge;

enum OrderStatus: string
{
  use HasBadge;

  case AwaitingPayment = 'awaiting_payment';
  case Paid = 'paid';
  case Expired = 'expired';
  case Cancelled = 'cancelled';
  case Refunded = 'refunded';

  public function label(): string
  {
    return match ($this) {
      self::AwaitingPayment => 'Aguardando pagamento',
      self::Paid => 'Pago',
      self::Expired => 'Expirado',
      self::Cancelled => 'Cancelado',
      self::Refunded => 'Reembolsado',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::AwaitingPayment => 'warning',
      self::Paid => 'success',
      self::Expired => 'secondary',
      self::Cancelled => 'danger',
      self::Refunded => 'info',
    };
  }
}
