<?php

namespace App\Domain\Support;

/**
 * Enums de status exibidos como badge Vuexy (bg-label-{color}).
 */
trait HasBadge
{
  abstract public function label(): string;

  abstract public function color(): string;

  public function badge(): string
  {
    return '<span class="badge bg-label-' . $this->color() . '">' . e($this->label()) . '</span>';
  }

  public static function options(): array
  {
    return collect(self::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()])->all();
  }
}
