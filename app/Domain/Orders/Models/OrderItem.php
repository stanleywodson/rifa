<?php

namespace App\Domain\Orders\Models;

use App\Domain\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
  public $timestamps = false;

  protected $fillable = ['order_id', 'ticket_id', 'number', 'price_cents'];

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  public function ticket(): BelongsTo
  {
    return $this->belongsTo(Ticket::class);
  }
}
