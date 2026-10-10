<?php

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
  protected $fillable = ['origin', 'event_id', 'event_type', 'payload', 'processed_at', 'error'];

  protected function casts(): array
  {
    return [
      'payload' => 'array',
      'processed_at' => 'datetime',
    ];
  }
}
