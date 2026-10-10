<?php

namespace App\Domain\Raffles\Enums;

use App\Domain\Support\HasBadge;

enum RaffleStatus: string
{
  use HasBadge;

  case Draft = 'draft';
  case PendingReview = 'pending_review';
  case ChangesRequested = 'changes_requested';
  case Rejected = 'rejected';
  case Published = 'published';
  case SalesClosed = 'sales_closed';
  case Drawn = 'drawn';
  case Completed = 'completed';
  case Cancelled = 'cancelled';

  public function label(): string
  {
    return match ($this) {
      self::Draft => 'Rascunho',
      self::PendingReview => 'Em análise',
      self::ChangesRequested => 'Ajustes solicitados',
      self::Rejected => 'Reprovada',
      self::Published => 'Publicada',
      self::SalesClosed => 'Vendas encerradas',
      self::Drawn => 'Sorteada',
      self::Completed => 'Concluída',
      self::Cancelled => 'Cancelada',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Draft => 'secondary',
      self::PendingReview => 'warning',
      self::ChangesRequested => 'info',
      self::Rejected => 'danger',
      self::Published => 'success',
      self::SalesClosed => 'primary',
      self::Drawn => 'dark',
      self::Completed => 'success',
      self::Cancelled => 'danger',
    };
  }
}
