<?php

namespace App\Policies;

use App\Domain\Orders\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
  public function view(User $user, Customer $customer): bool
  {
    return $user->belongsToTenant($customer->tenant);
  }
}
