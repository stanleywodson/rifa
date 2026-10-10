<?php

namespace App\Domain\Orders\Models;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Concerns\BelongsToTenant;
use App\Domain\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
  use BelongsToTenant, HasUlids;

  protected $fillable = [
    'tenant_id', 'raffle_id', 'customer_id', 'status', 'quantity', 'subtotal_cents',
    'discount_cents', 'total_cents', 'expires_at', 'paid_at', 'ip',
  ];

  protected function casts(): array
  {
    return [
      'status' => OrderStatus::class,
      'expires_at' => 'datetime',
      'paid_at' => 'datetime',
    ];
  }

  /** ULID somente na coluna pública "code"; a PK continua incremental. */
  public function uniqueIds(): array
  {
    return ['code'];
  }

  public function raffle(): BelongsTo
  {
    return $this->belongsTo(Raffle::class);
  }

  public function customer(): BelongsTo
  {
    return $this->belongsTo(Customer::class);
  }

  public function items(): HasMany
  {
    return $this->hasMany(OrderItem::class)->orderBy('number');
  }

  public function tickets(): HasMany
  {
    return $this->hasMany(Ticket::class);
  }

  public function payment(): HasOne
  {
    return $this->hasOne(Payment::class)->latestOfMany();
  }

  public function payments(): HasMany
  {
    return $this->hasMany(Payment::class);
  }

  public function isAwaitingPayment(): bool
  {
    return $this->status === OrderStatus::AwaitingPayment;
  }

  public function numbersFormatted(): array
  {
    return $this->items->map(fn($item) => $this->raffle->formatNumber($item->number))->all();
  }
}
