<?php

namespace App\Http\Controllers\PublicSite;

use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Tenants\Models\Tenant;
use App\Http\Controllers\Controller;

class TenantPageController extends Controller
{
  public function show(Tenant $tenant)
  {
    $raffles = $tenant->raffles()
      ->with(['media', 'prize'])
      ->whereIn('status', [RaffleStatus::Published, RaffleStatus::SalesClosed, RaffleStatus::Drawn, RaffleStatus::Completed])
      ->orderByRaw("FIELD(status, 'published', 'sales_closed', 'drawn', 'completed')")
      ->latest('published_at')
      ->get();

    return view('content.public.tenant', [
      'pageConfigs' => ['myLayout' => 'front'],
      'tenant' => $tenant,
      'raffles' => $raffles,
    ]);
  }
}
