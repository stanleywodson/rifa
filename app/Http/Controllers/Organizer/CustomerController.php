<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Customer;
use App\Http\Controllers\Controller;
use App\Support\DataTable;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
  public function index()
  {
    return view('content.organizer.customers.index', ['total' => Customer::query()->count()]);
  }

  public function data(Request $request)
  {
    $query = Customer::query()
      ->withCount(['orders as paid_orders' => fn($q) => $q->where('status', OrderStatus::Paid)])
      ->withSum(['orders as spent' => fn($q) => $q->where('status', OrderStatus::Paid)], 'total_cents')
      ->latest('id');

    return DataTable::make($request, $query, [0 => 'name', 2 => 'paid_orders', 3 => 'spent', 4 => 'created_at'],
      fn($q, $term) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
        ->when(only_digits($term) !== '', fn($q) => $q->orWhere('phone', 'like', '%' . only_digits($term) . '%')),
      fn(Customer $customer) => [
        'customer' => DataTable::person($customer->name, $customer->email ?: mask_phone($customer->phone), route('painel.compradores.show', $customer)),
        'cpf' => $customer->maskedCpf(),
        'orders' => $customer->paid_orders,
        'spent' => money_br((int) $customer->spent),
        'since' => DataTable::date($customer->created_at),
        'actions' => DataTable::actions(['Ver comprador' => route('painel.compradores.show', $customer)]),
      ]);
  }

  public function show(Customer $customer)
  {
    $this->authorize('view', $customer);
    $orders = $customer->orders()->with(['raffle', 'items'])->latest()->get();

    return view('content.organizer.customers.show', [
      'customer' => $customer,
      'orders' => $orders,
      'spent' => $orders->where('status', OrderStatus::Paid)->sum('total_cents'),
      'numbers' => $orders->where('status', OrderStatus::Paid)->sum('quantity'),
    ]);
  }
}
