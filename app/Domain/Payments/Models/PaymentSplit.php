<?php

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSplit extends Model
{
  protected $fillable = ['payment_id', 'recipient', 'wallet_id', 'amount_cents'];

  public function payment(): BelongsTo
  {
    return $this->belongsTo(Payment::class);
  }
}
