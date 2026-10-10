<?php

namespace Database\Seeders;

use App\Domain\Tenants\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
  public function run(): void
  {
    $plans = [
      ['code' => 'starter', 'name' => 'Starter', 'price_cents' => 0, 'fee_percent' => 8, 'limits' => ['active_raffles' => 1, 'max_numbers' => 1000]],
      ['code' => 'pro', 'name' => 'Pro', 'price_cents' => 9900, 'fee_percent' => 5, 'limits' => ['active_raffles' => 10, 'max_numbers' => 10000]],
      ['code' => 'business', 'name' => 'Business', 'price_cents' => 29900, 'fee_percent' => 3, 'limits' => ['active_raffles' => null, 'max_numbers' => 100000]],
    ];

    foreach ($plans as $plan) {
      Plan::query()->updateOrCreate(['code' => $plan['code']], $plan + ['is_active' => true]);
    }
  }
}
