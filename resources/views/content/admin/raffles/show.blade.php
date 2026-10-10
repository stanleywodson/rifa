@php
use App\Domain\Raffles\Enums\RaffleStatus as S;
$tenant = $raffle->tenant;
$awaitingDecision = $raffle->status === S::PendingReview && !$raffle->approved_at;
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Análise — ' . $raffle->title)

{{-- Base: apps/app-ecommerce-order-details --}}
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
  <div class="d-flex flex-column justify-content-center">
    <div class="mb-1"><span class="h5">{{ $raffle->title }}</span> <span class="ms-2">{!! $raffle->status->badge() !!}</span>
      @if ($raffle->approved_at && $raffle->status === S::PendingReview)<span class="badge bg-label-info">Gerando números</span>@endif
    </div>
    <p class="mb-0">Enviada em {{ $raffle->submitted_at?->format('d/m/Y H:i') ?? '—' }} · <a href="{{ route('admin.organizadores.show', $tenant->id) }}">{{ $tenant->name }}</a></p>
  </div>
  <div class="d-flex align-content-center flex-wrap gap-2">
    @if ($awaitingDecision)
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#decisionModal" data-action="{{ route('admin.rifas.approve', $raffle) }}" data-title="Aprovar rifa" data-required="0"><i class="ti ti-check me-1"></i>Aprovar</button>
      <button class="btn btn-label-warning" data-bs-toggle="modal" data-bs-target="#decisionModal" data-action="{{ route('admin.rifas.changes', $raffle) }}" data-title="Solicitar ajustes" data-required="1"><i class="ti ti-message-2 me-1"></i>Solicitar ajustes</button>
      <button class="btn btn-label-danger" data-bs-toggle="modal" data-bs-target="#decisionModal" data-action="{{ route('admin.rifas.reject', $raffle) }}" data-title="Reprovar rifa" data-required="1"><i class="ti ti-ban me-1"></i>Reprovar</button>
    @elseif (in_array($raffle->status, [S::Published, S::SalesClosed]))
      <a href="{{ $raffle->publicUrl() }}" target="_blank" class="btn btn-label-primary"><i class="ti ti-external-link me-1"></i>Página pública</a>
      <button class="btn btn-label-danger" data-bs-toggle="modal" data-bs-target="#decisionModal" data-action="{{ route('admin.rifas.cancel', $raffle) }}" data-title="Suspender e cancelar rifa (reembolso total)" data-required="1"><i class="ti ti-player-stop me-1"></i>Suspender</button>
    @endif
  </div>
</div>

<div class="row">
  <div class="col-12 col-lg-8">
    @if ($awaitingDecision)
      <div class="card mb-6">
        <div class="card-header"><h5 class="card-title m-0">Checklist de compliance</h5></div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="mb-2"><i class="ti {{ $tenant->isVerified() ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }} me-2"></i>KYC do organizador: {{ $tenant->kyc_status->label() }}</li>
            <li class="mb-2"><i class="ti {{ $raffle->authorization_doc_path ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }} me-2"></i>Documento de autorização
              @if ($raffle->authorization_doc_path)<a href="{{ route('admin.rifas.authorization', $raffle) }}" target="_blank">abrir</a>@endif</li>
            <li class="mb-2"><i class="ti {{ $raffle->authorization_number ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }} me-2"></i>Número da autorização: {{ $raffle->authorization_number ?: '—' }}</li>
            @foreach ($issues as $issue)
              <li class="mb-2"><i class="ti ti-alert-triangle text-warning me-2"></i>{{ $issue }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Dados da rifa</h5></div>
      <div class="card-body">
        @if ($raffle->media->isNotEmpty())
          <div class="d-flex flex-wrap gap-2 mb-6">
            @foreach ($raffle->media as $media)
              <a href="{{ $media->url() }}" target="_blank"><img src="{{ $media->url() }}" class="rounded object-fit-cover" width="110" height="80" alt=""></a>
            @endforeach
          </div>
        @endif
        <dl class="row mb-0">
          <dt class="col-sm-4">Prêmio</dt><dd class="col-sm-8">{{ $raffle->prize?->title }} @if($raffle->prize?->estimated_value_cents) ({{ money_br($raffle->prize->estimated_value_cents) }}) @endif</dd>
          <dt class="col-sm-4">Números</dt><dd class="col-sm-8">{{ number_format($raffle->total_numbers, 0, ',', '.') }} a {{ $raffle->priceFormatted() }} — máx. {{ money_br($raffle->total_numbers * $raffle->price_cents) }}</dd>
          <dt class="col-sm-4">Vendas até</dt><dd class="col-sm-8">{{ $raffle->sales_end_at?->format('d/m/Y H:i') }}</dd>
          <dt class="col-sm-4">Sorteio</dt><dd class="col-sm-8">{{ $raffle->draw_at?->format('d/m/Y H:i') }} — {{ $raffle->draw_method->label() }}</dd>
          <dt class="col-sm-4">Não vendido</dt><dd class="col-sm-8">{{ $raffle->unsold_rule->label() }}</dd>
          <dt class="col-sm-4">Vendidos</dt><dd class="col-sm-8">{{ $sold }}</dd>
        </dl>
        @if ($raffle->description)<hr><div>{!! $raffle->description !!}</div>@endif
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Regulamento</h5></div>
      <div class="card-body" style="white-space: pre-line">{{ $raffle->regulation }}</div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Organizador</h5></div>
      <div class="card-body">
        @include('content.admin.tenants._summary', ['tenant' => $tenant])
      </div>
    </div>
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Histórico</h5></div>
      <div class="card-body pt-1">
        <ul class="timeline pb-0 mb-0">
          @foreach ($logs as $log)
            <li class="timeline-item timeline-item-transparent {{ $loop->last ? 'border-transparent pb-0' : 'border-primary' }}">
              <span class="timeline-point timeline-point-primary"></span>
              <div class="timeline-event {{ $loop->last ? 'pb-0' : '' }}">
                <div class="timeline-header"><h6 class="mb-0">{{ $log->action }}</h6><small class="text-muted">{{ $log->created_at->format('d/m H:i') }}</small></div>
                <p class="mt-1 mb-0 small">{{ $log->user?->name ?? 'Sistema' }} {{ $log->after['note'] ?? '' }}</p>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Modal de decisão -->
<div class="modal fade" id="decisionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        @csrf
        <div class="modal-header"><h5 class="modal-title" data-modal-title></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label" for="notes">Observações para o organizador</label>
          <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  document.getElementById('decisionModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    this.querySelector('form').action = btn.dataset.action;
    this.querySelector('[data-modal-title]').textContent = btn.dataset.title;
    this.querySelector('textarea').required = btn.dataset.required === '1';
  });
</script>
@endsection
