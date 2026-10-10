<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Tenants\Models\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanController extends Controller
{
  public function index()
  {
    return view('content.admin.plans', ['plans' => Plan::query()->withCount('tenants')->orderBy('price_cents')->get()]);
  }

  public function update(Request $request, Plan $plan, AuditLogger $audit)
  {
    $data = $request->validate([
      'name' => ['required', 'string', 'max:60'],
      'price' => ['required', 'numeric', 'min:0'],
      'fee_percent' => ['required', 'numeric', 'min:0', 'max:30'],
      'active_raffles' => ['nullable', 'integer', 'min:1'],
      'max_numbers' => ['nullable', 'integer', 'min:10', 'max:' . config('saas.max_numbers')],
      'is_active' => ['boolean'],
    ], [], ['fee_percent' => 'taxa', 'active_raffles' => 'rifas ativas', 'max_numbers' => 'números por rifa']);

    $original = $plan->getAttributes();
    $plan->forceFill([
      'name' => $data['name'],
      'price_cents' => (int) round($data['price'] * 100),
      'fee_percent' => $data['fee_percent'],
      'limits' => array_merge($plan->limits ?? [], [
        'active_raffles' => $data['active_raffles'] ?? null,
        'max_numbers' => $data['max_numbers'] ?? null,
      ]),
      'is_active' => $request->boolean('is_active'),
    ])->save();
    $audit->logChanges('plan.updated', $plan, $original);

    return back()->with('success', "Plano {$plan->name} atualizado.");
  }
}
