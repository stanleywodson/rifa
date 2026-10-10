<?php

namespace App\Http\Controllers\PublicSite;

use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Plan;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
  public function index()
  {
    $raffles = Raffle::query()
      ->with(['tenant', 'media', 'prize'])
      ->where('status', RaffleStatus::Published)
      ->whereHas('tenant', fn($q) => $q->where('status', 'active'))
      ->latest('published_at')
      ->limit(6)
      ->get();

    return view('content.public.home', [
      'pageConfigs' => ['myLayout' => 'front'],
      'raffles' => $raffles,
      'plans' => Plan::query()->where('is_active', true)->orderBy('price_cents')->get(),
    ]);
  }
}
