<div class="d-flex justify-content-start align-items-center mb-4">
  <div class="avatar me-3"><span class="avatar-initial rounded bg-label-primary"><i class="ti ti-building-store"></i></span></div>
  <div class="d-flex flex-column">
    <a href="{{ route('admin.organizadores.show', $tenant->id) }}" class="text-body"><h6 class="mb-0">{{ $tenant->name }}</h6></a>
    <small>{{ $tenant->document }}</small>
  </div>
</div>
<p class="mb-1">KYC: {!! $tenant->kyc_status->badge() !!}</p>
<p class="mb-1">Status: {!! $tenant->status->badge() !!}</p>
<p class="mb-1">Plano: {{ $tenant->plan?->name ?? '—' }} · taxa {{ $tenant->feePercent() }}%</p>
<p class="mb-0">Recebimentos: {{ $tenant->hasPaymentAccount() ? 'configurado' : 'pendente' }}</p>
