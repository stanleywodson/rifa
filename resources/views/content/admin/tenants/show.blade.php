@php use App\Domain\Tenants\Enums\TenantStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', $tenant->name)

{{-- Base: apps/app-user-view-account --}}
@section('content')
<div class="row">
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">
    <div class="card mb-6">
      <div class="card-body pt-12">
        <div class="user-avatar-section">
          <div class="d-flex align-items-center flex-column">
            <div class="avatar avatar-xl mb-4"><span class="avatar-initial rounded bg-label-primary"><i class="ti ti-building-store ti-lg"></i></span></div>
            <div class="user-info text-center">
              <h5>{{ $tenant->name }}</h5>
              {!! $tenant->kyc_status->badge() !!} {!! $tenant->status->badge() !!}
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
          <div class="d-flex align-items-center me-5 gap-4">
            <div class="avatar"><div class="avatar-initial bg-label-primary rounded"><i class='ti ti-ticket ti-lg'></i></div></div>
            <div><h5 class="mb-0">{{ $tenant->raffles->count() }}</h5><span>Rifas</span></div>
          </div>
          <div class="d-flex align-items-center gap-4">
            <div class="avatar"><div class="avatar-initial bg-label-primary rounded"><i class='ti ti-currency-real ti-lg'></i></div></div>
            <div><h5 class="mb-0">{{ money_br($gmv) }}</h5><span>GMV</span></div>
          </div>
        </div>
        <h5 class="pb-4 border-bottom mb-4">Detalhes</h5>
        <ul class="list-unstyled mb-6">
          <li class="mb-2"><span class="h6">Documento:</span> <span>{{ $tenant->document }}</span></li>
          <li class="mb-2"><span class="h6">E-mail:</span> <span>{{ $tenant->email }}</span></li>
          <li class="mb-2"><span class="h6">Telefone:</span> <span>{{ $tenant->phone }}</span></li>
          <li class="mb-2"><span class="h6">Página:</span> <a href="{{ route('public.tenant', $tenant) }}" target="_blank">/{{ $tenant->slug }}</a></li>
          <li class="mb-2"><span class="h6">Recebimentos:</span> <span>{{ $tenant->hasPaymentAccount() ? $tenant->paymentGateway()->label() . ' · ' . $tenant->paymentAccountId() : ($tenant->hasStalePaymentAccount() ? 'inválido (' . $tenant->payment_gateway . ' não habilitado ou de outro ambiente)' : 'não configurado') }}</span></li>
          <li class="mb-2"><span class="h6">Pedidos pagos:</span> <span>{{ $orders }}</span></li>
        </ul>
        <h6>Usuários</h6>
        @foreach ($tenant->users as $member)
          <p class="mb-1">{{ $member->name }} <small class="text-muted">({{ \App\Domain\Tenants\Enums\TenantRole::from($member->pivot->role)->label() }}) — {{ $member->email }}</small></p>
        @endforeach
      </div>
    </div>
  </div>

  <div class="col-xl-8 col-lg-7">
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title mb-0">Verificação (KYC)</h5></div>
      <div class="card-body">
        <div class="table-responsive mb-4">
          <table class="table">
            <thead class="border-top"><tr><th>Documento</th><th>Arquivo</th><th>Enviado em</th></tr></thead>
            <tbody>
              @forelse ($tenant->kycDocuments as $doc)
                <tr>
                  <td>{{ $doc->typeLabel() }}</td>
                  <td><a href="{{ route('admin.organizadores.document', [$tenant->id, $doc]) }}" target="_blank">{{ $doc->original_name }}</a></td>
                  <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted">Nenhum documento.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <form method="POST" action="{{ route('admin.organizadores.kyc', $tenant->id) }}" class="row g-4">
          @csrf
          <div class="col-md-8"><input type="text" name="notes" class="form-control" placeholder="Observações (obrigatório ao reprovar)" value="{{ $tenant->kyc_notes }}"></div>
          <div class="col-md-4 d-flex gap-2">
            <button name="decision" value="approved" class="btn btn-success flex-fill">Aprovar</button>
            <button name="decision" value="rejected" class="btn btn-label-danger flex-fill">Reprovar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title mb-0">Conta e plano</h5></div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.organizadores.update', $tenant->id) }}" class="row g-4">
          @csrf @method('PUT')
          <div class="col-md-4">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
              @foreach (TenantStatus::cases() as $s)<option value="{{ $s->value }}" @selected($tenant->status === $s)>{{ $s->label() }}</option>@endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="plan_id">Plano</label>
            <select class="form-select" id="plan_id" name="plan_id">
              <option value="">—</option>
              @foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected($tenant->plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="fee_percent">Taxa (%) <small class="text-muted">vazio = plano</small></label>
            <input type="number" step="0.01" class="form-control" id="fee_percent" name="fee_percent" value="{{ $tenant->fee_percent }}">
          </div>
          <div class="col-12"><button class="btn btn-primary">Salvar</button></div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Rifas</h5></div>
      <div class="table-responsive">
        <table class="table">
          <thead class="border-top"><tr><th>Rifa</th><th>Status</th><th>Criada</th></tr></thead>
          <tbody>
            @foreach ($tenant->raffles as $raffle)
              <tr>
                <td><a href="{{ route('admin.rifas.show', $raffle) }}">{{ $raffle->title }}</a></td>
                <td>{!! $raffle->status->badge() !!}</td>
                <td>{{ $raffle->created_at->format('d/m/Y') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
