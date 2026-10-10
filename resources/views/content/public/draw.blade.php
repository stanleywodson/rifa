@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Prova do sorteio — ' . $raffle->title)

@section('content')
<section class="section-py bg-body first-section-pt">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-9">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
          <div>
            <h4 class="mb-1">Prova pública do sorteio</h4>
            <p class="mb-0"><a href="{{ route('public.raffle', [$tenant, $raffle]) }}">{{ $raffle->title }}</a> · {{ $tenant->name }}</p>
          </div>
          @if ($proofs->first()['draw']->minutes_pdf_path ?? null)
            <a href="{{ route('public.draw.minutes', [$tenant, $raffle]) }}" class="btn btn-primary"><i class="ti ti-file-type-pdf me-1"></i>Baixar ata (PDF)</a>
          @endif
        </div>

        <div class="card mb-6">
          <div class="card-header"><h5 class="card-title mb-0">Regras definidas antes das vendas</h5></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-4">Método</dt><dd class="col-sm-8">{{ $raffle->draw_method->label() }}</dd>
              <dt class="col-sm-4">Números</dt><dd class="col-sm-8">{{ $raffle->formatNumber($raffle->number_start) }} a {{ $raffle->formatNumber($raffle->lastNumber()) }} ({{ $raffle->total_numbers }})</dd>
              <dt class="col-sm-4">Número não vendido</dt><dd class="col-sm-8">{{ $raffle->unsold_rule->label() }}</dd>
              @if ($raffle->commit_hash)
                <dt class="col-sm-4">Commit (SHA-256)</dt><dd class="col-sm-8"><code class="text-break">{{ $raffle->commit_hash }}</code><br><small class="text-muted">Publicado em {{ $raffle->approved_at?->format('d/m/Y H:i') }}</small></dd>
              @endif
              <dt class="col-sm-4">Vendas encerradas</dt><dd class="col-sm-8">{{ $raffle->sales_closed_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
            </dl>
          </div>
        </div>

        @forelse ($proofs as $proof)
          @php $d = $proof['draw']; @endphp
          <div class="card mb-6">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Sorteio de {{ $d->executed_at->format('d/m/Y H:i') }}</h5>
              {!! $d->outcome->badge() !!}
            </div>
            <div class="card-body">
              @if ($d->drawn_number !== null)
                <div class="text-center p-6 bg-label-success rounded mb-6">
                  <small class="d-block">Número contemplado</small>
                  <span class="display-4 fw-bold">{{ $raffle->formatNumber($d->drawn_number) }}</span>
                  <div>{{ \Illuminate\Support\Str::of($d->customer?->name)->explode(' ')->first() }} · CPF {{ $d->customer?->maskedCpf() }}</div>
                </div>
              @endif
              <ul class="timeline mb-0">
                <li class="timeline-item timeline-item-transparent">
                  <span class="timeline-point timeline-point-primary"></span>
                  <div class="timeline-event">
                    <div class="timeline-header mb-2"><h6 class="mb-0">Fonte do resultado</h6></div>
                    <p class="mb-2">{{ $d->source_ref }}</p>
                    @if (!empty($d->raw_data['fonte']))<small><a href="{{ $d->raw_data['fonte'] }}" target="_blank" rel="noopener">{{ $d->raw_data['fonte'] }}</a></small>@endif
                    @if (!empty($d->raw_data['informado_manualmente']))<div><span class="badge bg-label-warning mt-2">Resultado informado manualmente pelo organizador</span></div>@endif
                  </div>
                </li>
                <li class="timeline-item timeline-item-transparent">
                  <span class="timeline-point timeline-point-info"></span>
                  <div class="timeline-event">
                    <div class="timeline-header mb-2"><h6 class="mb-0">Cálculo</h6></div>
                    @if (!empty($d->raw_data['formula']))<p class="mb-1"><code>{{ $d->raw_data['formula'] }}</code></p>@endif
                    @if ($d->seed_revealed)<p class="mb-1 small">Semente revelada: <code class="text-break">{{ $d->seed_revealed }}</code></p>@endif
                    <p class="mb-1">Número calculado: <strong>{{ $d->computed_number }}</strong>
                      @if ($proof['recomputed'] === $d->computed_number)
                        <span class="badge bg-label-success ms-1"><i class="ti ti-check ti-xs"></i> recalculado e confere</span>
                      @else
                        <span class="badge bg-label-danger ms-1">não confere</span>
                      @endif
                    </p>
                    <p class="mb-0">{{ $d->raw_data['regra_aplicada'] ?? '' }}</p>
                  </div>
                </li>
                <li class="timeline-item timeline-item-transparent border-transparent">
                  <span class="timeline-point timeline-point-success"></span>
                  <div class="timeline-event">
                    <div class="timeline-header mb-2"><h6 class="mb-0">Registro imutável</h6></div>
                    <p class="mb-1 small">Hash: <code class="text-break">{{ $d->hash }}</code>
                      <span class="badge {{ $proof['hashValid'] ? 'bg-label-success' : 'bg-label-danger' }}">{{ $proof['hashValid'] ? 'íntegro' : 'adulterado' }}</span></p>
                    <p class="mb-0 small">Hash anterior: <code class="text-break">{{ $d->prev_hash ?? '—' }}</code></p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        @empty
          <div class="alert alert-secondary">O sorteio ainda não foi realizado.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>
@endsection
