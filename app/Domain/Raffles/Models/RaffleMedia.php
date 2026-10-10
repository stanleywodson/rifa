<?php

namespace App\Domain\Raffles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RaffleMedia extends Model
{
  protected $fillable = ['raffle_id', 'type', 'path', 'sort'];

  public function raffle(): BelongsTo
  {
    return $this->belongsTo(Raffle::class);
  }

  public function url(): string
  {
    return Storage::disk('public')->url($this->path);
  }
}
