<?php

namespace App\Domain\Orders\Models;

use App\Domain\Tenants\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
  use BelongsToTenant;

  protected $fillable = ['tenant_id', 'name', 'cpf', 'email', 'phone', 'birthdate', 'gateway_customer_id'];

  protected function casts(): array
  {
    return ['birthdate' => 'date'];
  }

  public function orders(): HasMany
  {
    return $this->hasMany(Order::class);
  }

  public function maskedCpf(): string
  {
    return mask_cpf($this->cpf);
  }
}
