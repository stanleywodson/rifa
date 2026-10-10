<?php

namespace App\Domain\Raffles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RafflePrize extends Model
{
  protected $fillable = ['raffle_id', 'position', 'title', 'description', 'estimated_value_cents'];

  public function raffle(): BelongsTo
  {
    return $this->belongsTo(Raffle::class);
  }
}
