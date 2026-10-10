<?php

namespace App\Domain\Tickets\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tickets\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
  public $timestamps = false;

  protected $fillable = ['raffle_id', 'number', 'status', 'order_id', 'reserved_until', 'paid_at'];

  protected function casts(): array
  {
    return [
      'status' => TicketStatus::class,
      'reserved_until' => 'datetime',
      'paid_at' => 'datetime',
    ];
  }

  public function raffle(): BelongsTo
  {
    return $this->belongsTo(Raffle::class);
  }

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }
}
