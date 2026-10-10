<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Http\Controllers\Controller;
use App\Support\DataTable;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
  public function index()
  {
    $paid = Payment::query()->where('status', PaymentStatus::Paid);

    return view('content.admin.finance', [
      'gross' => (int) (clone $paid)->sum('amount_cents'),
      'count' => (clone $paid)->count(),
      'pending' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
      'refunded' => (int) Payment::query()->where('status', PaymentStatus::Refunded)->sum('amount_cents'),
      'failedRefunds' => Refund::query()->with('payment.order')->where('status', 'failed')->latest()->get(),
    ]);
  }

  public function data(Request $request)
  {
    $query = Payment::query()->with(['order' => fn($q) => $q->withoutGlobalScopes()->with('raffle.tenant', 'customer'), 'splits'])
      ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
      ->latest('id');

    return DataTable::make($request, $query, [0 => 'id', 4 => 'amount_cents', 6 => 'status', 7 => 'created_at'],
      fn($q, $term) => $q->where('gateway_id', 'like', "%{$term}%")->orWhereHas('order', fn($o) => $o->withoutGlobalScopes()->where('code', 'like', "%{$term}%")),
      fn(Payment $payment) => [
        'id' => '<span class="text-nowrap">' . e($payment->gateway_id) . '</span>',
        'tenant' => e($payment->order->raffle->tenant->name),
        'raffle' => e($payment->order->raffle->title),
        'customer' => e($payment->order->customer->name),
        'amount' => money_br($payment->amount_cents),
        'fee' => money_br((int) $payment->splits->firstWhere('recipient', 'platform')?->amount_cents),
        'status' => $payment->status->badge(),
        'date' => DataTable::date($payment->paid_at ?? $payment->created_at),
      ]);
  }
}
