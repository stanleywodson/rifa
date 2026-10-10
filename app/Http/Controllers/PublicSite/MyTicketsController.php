<?php

namespace App\Http\Controllers\PublicSite;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * "Meus números": consulta por CPF ou telefone + código de um pedido
 * (o código evita enumeração de compras de terceiros apenas pelo CPF).
 */
class MyTicketsController extends Controller
{
  public function index()
  {
    return view('content.public.my-tickets', ['pageConfigs' => ['myLayout' => 'front'], 'orders' => null]);
  }

  public function lookup(Request $request)
  {
    $data = $request->validate([
      'document' => ['required', 'string', 'max:20'],
      'code' => ['required', 'string', 'size:26'],
    ], [], ['document' => 'CPF ou telefone', 'code' => 'código do pedido']);

    $digits = only_digits($data['document']);
    $anchor = Order::query()->withoutGlobalScopes()->with('customer')->where('code', strtolower(trim($data['code'])))->first();

    $matches = $anchor && in_array($digits, [$anchor->customer->cpf, $anchor->customer->phone], true);
    if (!$matches) {
      return back()->withInput()->with('error', 'Nenhum pedido encontrado com esses dados.');
    }

    $orders = Order::query()->withoutGlobalScopes()
      ->with(['raffle.tenant', 'items', 'raffle.lastDraw'])
      ->where('customer_id', $anchor->customer_id)
      ->whereIn('status', [OrderStatus::Paid, OrderStatus::AwaitingPayment, OrderStatus::Refunded])
      ->latest()
      ->get();

    return view('content.public.my-tickets', [
      'pageConfigs' => ['myLayout' => 'front'],
      'orders' => $orders,
      'customer' => $anchor->customer,
    ]);
  }
}
