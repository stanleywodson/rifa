<?php

namespace App\Http\Controllers\PublicSite;

use App\Domain\Orders\CheckoutService;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tickets\Exceptions\NumbersUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
  public function create(Request $request, Tenant $tenant, Raffle $raffle)
  {
    abort_unless($raffle->isPubliclyVisible(), 404);
    if (!$raffle->isOnSale()) {
      return redirect()->route('public.raffle', [$tenant, $raffle])->with('error', 'As vendas desta rifa não estão abertas.');
    }

    $numbers = collect(explode(',', (string) $request->query('numeros')))
      ->map(fn($n) => trim($n))->filter(fn($n) => $n !== '' && ctype_digit($n))->map('intval')->unique()->sort()->values();
    $quantity = $numbers->isEmpty() ? max((int) $request->query('quantidade', $raffle->min_numbers_per_order), 1) : $numbers->count();

    return view('content.public.checkout', [
      'pageConfigs' => ['myLayout' => 'front'],
      'tenant' => $tenant,
      'raffle' => $raffle->load('media', 'prize'),
      'numbers' => $numbers,
      'quantity' => $quantity,
    ]);
  }

  public function store(CheckoutRequest $request, Tenant $tenant, Raffle $raffle, CheckoutService $checkout)
  {
    abort_unless($raffle->isPubliclyVisible(), 404);
    $data = $request->validated();

    try {
      $order = $checkout->checkout(
        $raffle,
        $request->only(['name', 'cpf', 'email', 'phone', 'birthdate']),
        $data['numbers'] ?? [],
        (int) ($data['quantity'] ?? 0),
        $request->ip(),
      );
    } catch (NumbersUnavailable $e) {
      return redirect()->route('public.raffle', [$tenant, $raffle])->with('error', $e->getMessage());
    }

    return redirect()->route('public.order', $order->code);
  }
}
