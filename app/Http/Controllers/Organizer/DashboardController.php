<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tenants\TenantContext;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
  public function index(TenantContext $context)
  {
    $tenant = $context->get();
    $since = now()->subDays(29)->startOfDay();

    $orders = Order::query()->where('created_at', '>=', $since);
    $paid = (clone $orders)->where('status', OrderStatus::Paid);
    $totalOrders = (clone $orders)->count();
    $paidCount = (clone $paid)->count();

    // Vendas por dia (últimos 30 dias)
    $daily = (clone $paid)->selectRaw('DATE(paid_at) as day, SUM(total_cents) as amount, COUNT(*) as total')
      ->groupBy('day')->pluck('amount', 'day');
    $days = collect(range(0, 29))->map(fn($i) => $since->copy()->addDays($i)->toDateString());

    $raffles = Raffle::query()->with('media')
      ->withCount(['tickets as sold_count' => fn($q) => $q->where('status', TicketStatus::Paid)])
      ->whereIn('status', [RaffleStatus::Published, RaffleStatus::SalesClosed, RaffleStatus::PendingReview, RaffleStatus::Drawn])
      ->latest()->limit(5)->get();

    return view('content.organizer.dashboard', [
      'tenant' => $tenant,
      'revenue' => (int) (clone $paid)->sum('total_cents'),
      'revenueAll' => (int) Order::query()->where('status', OrderStatus::Paid)->sum('total_cents'),
      'paidCount' => $paidCount,
      'soldNumbers' => (int) (clone $paid)->sum('quantity'),
      'conversion' => $totalOrders ? round($paidCount * 100 / $totalOrders, 1) : 0,
      'expiredRate' => $totalOrders ? round((clone $orders)->where('status', OrderStatus::Expired)->count() * 100 / $totalOrders, 1) : 0,
      'activeRaffles' => Raffle::query()->where('status', RaffleStatus::Published)->count(),
      'pendingReview' => Raffle::query()->where('status', RaffleStatus::PendingReview)->count(),
      'chart' => [
        'labels' => $days->map(fn($d) => Carbon::parse($d)->format('d/m'))->values(),
        'series' => $days->map(fn($d) => round(($daily[$d] ?? 0) / 100, 2))->values(),
      ],
      'raffles' => $raffles,
      'recentOrders' => Order::query()->with(['customer', 'raffle'])->where('status', OrderStatus::Paid)->latest('paid_at')->limit(6)->get(),
      'fee' => $tenant->feePercent(),
    ]);
  }

  public function switchTenant(Request $request, Tenant $tenant)
  {
    abort_unless($request->user()->belongsToTenant($tenant), 403);
    $request->session()->put('tenant_id', $tenant->id);

    return redirect()->route('painel.dashboard');
  }
}
