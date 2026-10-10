<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Enums\TenantStatus;
use App\Domain\Tenants\Models\KycDocument;
use App\Domain\Tenants\Models\Plan;
use App\Domain\Tenants\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
  public function __construct(protected AuditLogger $audit) {}

  public function index()
  {
    return view('content.admin.tenants.index', [
      'total' => Tenant::query()->count(),
      'kyc' => Tenant::query()->selectRaw('kyc_status, count(*) as total')->groupBy('kyc_status')->pluck('total', 'kyc_status'),
      'suspended' => Tenant::query()->where('status', TenantStatus::Suspended)->count(),
    ]);
  }

  public function data(Request $request)
  {
    $query = Tenant::query()->with('plan')->withCount('raffles')
      ->when($request->filled('kyc'), fn($q) => $q->where('kyc_status', $request->input('kyc')))
      ->latest('id');

    return DataTable::make($request, $query, [0 => 'name', 3 => 'raffles_count', 5 => 'created_at'],
      fn($q, $term) => $q->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"),
      fn(Tenant $tenant) => [
        'tenant' => DataTable::person($tenant->name, $tenant->email, route('admin.organizadores.show', $tenant->id)),
        'plan' => e($tenant->plan?->name ?? '—'),
        'kyc' => $tenant->kyc_status->badge(),
        'raffles' => $tenant->raffles_count,
        'status' => $tenant->status->badge(),
        'since' => DataTable::date($tenant->created_at),
        'actions' => DataTable::actions(['Ver organizador' => route('admin.organizadores.show', $tenant->id)]),
      ]);
  }

  public function show(Tenant $tenant)
  {
    $tenant->load(['plan', 'users', 'kycDocuments', 'raffles' => fn($q) => $q->latest()]);

    return view('content.admin.tenants.show', [
      'tenant' => $tenant,
      'plans' => Plan::query()->orderBy('price_cents')->get(),
      'gmv' => (int) Order::query()->where('tenant_id', $tenant->id)->where('status', OrderStatus::Paid)->sum('total_cents'),
      'orders' => Order::query()->where('tenant_id', $tenant->id)->where('status', OrderStatus::Paid)->count(),
    ]);
  }

  public function update(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'status' => ['required', Rule::enum(TenantStatus::class)],
      'plan_id' => ['nullable', 'exists:plans,id'],
      'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:30'],
    ], [], ['plan_id' => 'plano', 'fee_percent' => 'taxa']);

    $original = $tenant->getAttributes();
    $tenant->forceFill($data)->save();
    $this->audit->logChanges('tenant.admin_updated', $tenant, $original);

    return back()->with('success', 'Organizador atualizado.');
  }

  public function kyc(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'decision' => ['required', Rule::in([KycStatus::Approved->value, KycStatus::Rejected->value])],
      'notes' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
    ], [], ['notes' => 'observações']);

    $original = $tenant->getAttributes();
    $tenant->forceFill(['kyc_status' => $data['decision'], 'kyc_notes' => $data['notes'] ?? null, 'kyc_reviewed_at' => now()])->save();
    $this->audit->logChanges('tenant.kyc_' . $data['decision'], $tenant, $original);

    return back()->with('success', $data['decision'] === 'approved' ? 'Organizador verificado.' : 'Verificação reprovada.');
  }

  public function document(Tenant $tenant, KycDocument $document)
  {
    abort_unless($document->tenant_id === $tenant->id && Storage::disk('local')->exists($document->path), 404);

    return Storage::disk('local')->response($document->path, $document->original_name);
  }
}
