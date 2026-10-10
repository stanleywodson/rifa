<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
  protected $fillable = [
    'order_id', 'gateway', 'gateway_id', 'method', 'status', 'amount_cents',
    'pix_qr_image', 'pix_copy_paste', 'paid_at', 'payload',
  ];

  protected function casts(): array
  {
    return [
      'status' => PaymentStatus::class,
      'paid_at' => 'datetime',
      'payload' => 'array',
    ];
  }

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  public function splits(): HasMany
  {
    return $this->hasMany(PaymentSplit::class);
  }

  public function refunds(): HasMany
  {
    return $this->hasMany(Refund::class);
  }
}
