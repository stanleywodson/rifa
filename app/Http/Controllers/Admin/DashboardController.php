<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\PaymentSplit;
use App\Domain\Payments\Models\Refund;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
  public function index()
  {
    $since = now()->subDays(29)->startOfDay();
    $paid = Order::query()->where('status', OrderStatus::Paid);

    $daily = (clone $paid)->where('paid_at', '>=', $since)
      ->selectRaw('DATE(paid_at) as day, SUM(total_cents) as amount')->groupBy('day')->pluck('amount', 'day');
    $days = collect(range(0, 29))->map(fn($i) => $since->copy()->addDays($i)->toDateString());

    return view('content.admin.dashboard', [
      'gmv' => (int) (clone $paid)->sum('total_cents'),
      'gmv30' => (int) (clone $paid)->where('paid_at', '>=', $since)->sum('total_cents'),
      'fees' => (int) PaymentSplit::query()->where('recipient', 'platform')
        ->whereHas('payment', fn($q) => $q->where('status', 'paid'))->sum('amount_cents'),
      'tenants' => Tenant::query()->count(),
      'kycPending' => Tenant::query()->where('kyc_status', KycStatus::Submitted)->count(),
      'pendingRaffles' => Raffle::query()->where('status', RaffleStatus::PendingReview)->whereNull('approved_at')->count(),
      'activeRaffles' => Raffle::query()->where('status', RaffleStatus::Published)->count(),
      'failedRefunds' => Refund::query()->where('status', 'failed')->count(),
      'queue' => Raffle::query()->with('tenant')->where('status', RaffleStatus::PendingReview)->whereNull('approved_at')->oldest('submitted_at')->limit(6)->get(),
      'chart' => [
        'labels' => $days->map(fn($d) => Carbon::parse($d)->format('d/m'))->values(),
        'series' => $days->map(fn($d) => round(($daily[$d] ?? 0) / 100, 2))->values(),
      ],
    ]);
  }
}
