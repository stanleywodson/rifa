<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Models\Raffle;
use App\Http\Controllers\Controller;
use App\Support\DataTable;
use Illuminate\Http\Request;

class OrderController extends Controller
{
  public function index(Request $request)
  {
    return view('content.organizer.orders.index', [
      'counts' => Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
      'raffles' => Raffle::query()->orderBy('title')->pluck('title', 'id'),
      'raffleId' => $request->integer('raffle') ?: null,
    ]);
  }

  public function data(Request $request)
  {
    $query = Order::query()->with(['customer', 'raffle'])
      ->when($request->filled('raffle'), fn($q) => $q->where('raffle_id', $request->integer('raffle')))
      ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
      ->when($request->filled('customer'), fn($q) => $q->where('customer_id', $request->integer('customer')))
      ->latest('id');

    return DataTable::make($request, $query, [0 => 'id', 1 => 'created_at', 4 => 'quantity', 5 => 'total_cents', 6 => 'status'],
      fn($q, $term) => $q->where('code', 'like', "%{$term}%")
        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', '%' . only_digits($term) . '%')),
      fn(Order $order) => [
        'order' => DataTable::link(route('painel.pedidos.show', $order), '#' . strtoupper(substr($order->code, -8))),
        'date' => DataTable::date($order->created_at),
        'customer' => DataTable::person($order->customer->name, mask_phone($order->customer->phone), route('painel.compradores.show', $order->customer)),
        'raffle' => e($order->raffle->title),
        'quantity' => $order->quantity,
        'total' => money_br($order->total_cents),
        'status' => $order->status->badge(),
        'actions' => DataTable::actions(['Ver pedido' => route('painel.pedidos.show', $order)]),
      ]);
  }

  public function show(Order $order)
  {
    $this->authorize('view', $order);
    $order->load(['customer', 'raffle', 'items', 'payments.splits', 'payments.refunds']);

    return view('content.organizer.orders.show', ['order' => $order]);
  }
}
