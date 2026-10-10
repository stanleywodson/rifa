<?php

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
  protected $fillable = ['payment_id', 'reason', 'amount_cents', 'status', 'payload'];

  protected function casts(): array
  {
    return ['payload' => 'array'];
  }

  public function payment(): BelongsTo
  {
    return $this->belongsTo(Payment::class);
  }
}
