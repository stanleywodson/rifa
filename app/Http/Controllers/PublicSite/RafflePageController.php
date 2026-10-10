<?php

namespace App\Http\Controllers\PublicSite;

use App\Domain\Draws\DrawService;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tickets\TicketReservationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RafflePageController extends Controller
{
  public function show(Tenant $tenant, Raffle $raffle)
  {
    abort_unless($raffle->isPubliclyVisible(), 404);
    $raffle->load(['media', 'prize', 'lastDraw.ticket']);

    return view('content.public.raffle', [
      'pageConfigs' => ['myLayout' => 'front'],
      'tenant' => $tenant,
      'raffle' => $raffle,
      'sold' => $raffle->soldCount(),
      'gridPageSize' => config('saas.grid_page_size'),
    ]);
  }

  public function grid(Request $request, Tenant $tenant, Raffle $raffle, TicketReservationService $reservations)
  {
    abort_unless($raffle->isPubliclyVisible(), 404);

    return response()->json($reservations->gridPage($raffle, (int) $request->query('page', 1)));
  }

  public function draw(Tenant $tenant, Raffle $raffle, DrawService $draws)
  {
    abort_unless($raffle->isPubliclyVisible(), 404);
    $raffle->load(['prize', 'draws.ticket', 'draws.customer']);

    return view('content.public.draw', [
      'pageConfigs' => ['myLayout' => 'front'],
      'tenant' => $tenant,
      'raffle' => $raffle,
      'proofs' => $raffle->draws->map(fn($draw) => [
        'draw' => $draw,
        'recomputed' => $draws->recompute($draw),
        'hashValid' => $draws->verifyHash($draw),
      ]),
    ]);
  }

  public function minutes(Tenant $tenant, Raffle $raffle)
  {
    abort_unless($raffle->isPubliclyVisible(), 404);
    $draw = $raffle->draws()->whereNotNull('minutes_pdf_path')->first();
    abort_unless($draw && Storage::disk('local')->exists($draw->minutes_pdf_path), 404);

    return Storage::disk('local')->response($draw->minutes_pdf_path, "ata-sorteio-{$raffle->slug}.pdf", ['Content-Type' => 'application/pdf']);
  }
}
