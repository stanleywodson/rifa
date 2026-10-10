<?php

namespace App\Domain\Tenants\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
  protected $fillable = ['code', 'name', 'price_cents', 'fee_percent', 'limits', 'is_active'];

  protected function casts(): array
  {
    return [
      'limits' => 'array',
      'is_active' => 'boolean',
      'fee_percent' => 'float',
    ];
  }

  public function limit(string $key, mixed $default = null): mixed
  {
    return $this->limits[$key] ?? $default;
  }

  public function tenants(): HasMany
  {
    return $this->hasMany(Tenant::class);
  }
}
