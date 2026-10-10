<?php

namespace App\Policies;

use App\Domain\Orders\Models\Order;
use App\Models\User;

class OrderPolicy
{
  public function view(User $user, Order $order): bool
  {
    return $user->belongsToTenant($order->tenant);
  }
}
