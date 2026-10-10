@extends('layouts/layoutMaster')

@section('title', 'Planos')

{{-- Base: pages/pages-pricing --}}
@section('content')
<h4 class="mb-1">Planos</h4>
<p class="mb-6">Limites aplicados no servidor ao criar e enviar rifas. Alterações valem para todos os organizadores do plano.</p>
<div class="row g-6">
  @foreach ($plans as $plan)
    <div class="col-lg-4">
      <div class="card border shadow-none h-100">
        <div class="card-body">
          <form method="POST" action="{{ route('admin.planos.update', $plan) }}">
            @csrf @method('PUT')
            <div class="d-flex justify-content-between align-items-center mb-4">
              <span class="badge bg-label-primary">{{ $plan->code }}</span>
              <small class="text-muted">{{ $plan->tenants_count }} organizador(es)</small>
            </div>
            <div class="mb-4"><label class="form-label">Nome</label><input class="form-control" name="name" value="{{ $plan->name }}" required></div>
            <div class="row g-4 mb-4">
              <div class="col-6"><label class="form-label">Preço/mês (R$)</label><input type="number" step="0.01" class="form-control" name="price" value="{{ $plan->price_cents / 100 }}" required></div>
              <div class="col-6"><label class="form-label">Taxa (%)</label><input type="number" step="0.01" class="form-control" name="fee_percent" value="{{ $plan->fee_percent }}" required></div>
              <div class="col-6"><label class="form-label">Rifas ativas</label><input type="number" class="form-control" name="active_raffles" value="{{ $plan->limit('active_raffles') }}" placeholder="ilimitado"></div>
              <div class="col-6"><label class="form-label">Números/rifa</label><input type="number" class="form-control" name="max_numbers" value="{{ $plan->limit('max_numbers') }}" placeholder="padrão"></div>
            </div>
            <div class="form-check form-switch mb-6">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $plan->id }}" @checked($plan->is_active)>
              <label class="form-check-label" for="active{{ $plan->id }}">Disponível para novos cadastros</label>
            </div>
            <button class="btn btn-primary w-100">Salvar</button>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection
