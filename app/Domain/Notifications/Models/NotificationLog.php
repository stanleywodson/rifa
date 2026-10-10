<?php

namespace App\Domain\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
  protected $table = 'notifications_log';

  protected $fillable = ['tenant_id', 'channel', 'recipient', 'template', 'status', 'payload'];

  protected function casts(): array
  {
    return ['payload' => 'array'];
  }
}
