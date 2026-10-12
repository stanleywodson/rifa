@php
use App\Domain\Raffles\Enums\RaffleStatus as S;
$user = auth()->user();
$tab = request('aba', 'geral');
$paidTickets = $tickets['paid'] ?? 0;
$reserved = $tickets['reserved'] ?? 0;
$platformFee = (int) round($revenue * $fee / 100);
@endphp

@extends('layouts/layoutMaster')

@section('title', $raffle->title)

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-datatable.js', 'resources/assets/js/saas-forms.js'])
@endsection

{{-- Base: apps/app-ecommerce-customer-details-overview --}}
@section('content')
@if (!$raffle->tenant->canReceivePayments())
  <div class="alert alert-danger d-flex align-items-center mb-6">
    <span class="alert-icon rounded"><i class="ti ti-building-bank"></i></span>
    <span class="flex-grow-1">{{ $raffle->tenant->hasStalePaymentAccount() ? 'Sua conta de recebimento foi criada em outro ambiente e não é válida.' : 'Configure a conta de recebimento.' }} Enquanto isso, as vendas das suas rifas ficam bloqueadas.</span>
    <a href="{{ route('painel.configuracoes.edit', ['aba' => 'recebimentos']) }}" class="btn btn-sm btn-danger">Configurar recebimentos</a>
  </div>
@endif
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-sm-between mb-6 text-center text-sm-start gap-2">
  <div class="mb-2 mb-sm-0">
    <div class="mb-1"><span class="h4">{{ $raffle->title }}</span> <span class="ms-2 align-text-bottom">{!! $raffle->status->badge() !!}</span></div>
    <p class="mb-0">Criada em {{ $raffle->created_at->format('d/m/Y H:i') }}
      @if ($raffle->isPubliclyVisible()) · <a href="{{ $raffle->publicUrl() }}" target="_blank">{{ $raffle->publicUrl() }}</a>@endif
    </p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    @can('update', $raffle)
      <a href="{{ route('painel.rifas.edit', $raffle) }}" class="btn btn-label-primary"><i class="ti ti-pencil me-1"></i>Editar</a>
    @endcan
    @can('submit', $raffle)
      <form method="POST" action="{{ route('painel.rifas.submit', $raffle) }}" data-confirm="Enviar a rifa para análise?" data-confirm-text="Durante a análise a rifa não pode ser editada.">
        @csrf
        <button class="btn btn-primary" @disabled(count($issues))><i class="ti ti-send me-1"></i>Enviar para análise</button>
      </form>
    @endcan
    @if ($raffle->status === S::ChangesRequested && $user->isOwnerOf($raffle->tenant))
      <form method="POST" action="{{ route('painel.rifas.reopen', $raffle) }}">@csrf<button class="btn btn-label-secondary">Voltar para rascunho</button></form>
    @endif
    @can('draw', $raffle)
      <a href="{{ route('painel.rifas.draw.create', $raffle) }}" class="btn btn-success"><i class="ti ti-trophy me-1"></i>Realizar sorteio</a>
    @endcan
    <div class="btn-group">
      <button type="button" class="btn btn-label-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-download me-1"></i>Relatórios</button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('painel.rifas.export.csv', $raffle) }}"><i class="ti ti-file-spreadsheet me-2"></i>Compradores (CSV)</a></li>
        <li><a class="dropdown-item" href="{{ route('painel.rifas.export.pdf', $raffle) }}"><i class="ti ti-file-type-pdf me-2"></i>Relatório final (PDF)</a></li>
      </ul>
    </div>
    @can('delete', $raffle)
      <form method="POST" action="{{ route('painel.rifas.destroy', $raffle) }}" data-confirm="Excluir este rascunho?">@csrf @method('DELETE')<button class="btn btn-label-danger"><i class="ti ti-trash"></i></button></form>
    @endcan
  </div>
</div>

<div class="row">
  <!-- Sidebar -->
  <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
    <div class="card mb-6">
      <div class="card-body pt-8">
        <div class="d-flex align-items-center flex-column">
          @if ($cover = $raffle->coverUrl())
            <img class="img-fluid rounded mb-4 object-fit-cover" src="{{ $cover }}" style="height: 160px; width: 100%" alt="">
          @endif
          <div class="text-center mb-6">
            <h5 class="mb-0">{{ $raffle->prize?->title ?? 'Prêmio não cadastrado' }}</h5>
            @if ($raffle->prize?->estimated_value_cents)<span>Valor estimado {{ money_br($raffle->prize->estimated_value_cents) }}</span>@endif
          </div>
        </div>
        <div class="d-flex justify-content-around flex-wrap mb-6 gap-0 gap-md-3 gap-lg-4">
          <div class="d-flex align-items-center gap-4 me-5">
            <div class="avatar"><div class="avatar-initial rounded bg-label-primary"><i class='ti ti-ticket ti-lg'></i></div></div>
            <div><h5 class="mb-0">{{ $paidTickets }}</h5><span>Vendidos</span></div>
          </div>
          <div class="d-flex align-items-center gap-4">
            <div class="avatar"><div class="avatar-initial rounded bg-label-success"><i class='ti ti-currency-real ti-lg'></i></div></div>
            <div><h5 class="mb-0">{{ money_br($revenue) }}</h5><span>Arrecadado</span></div>
          </div>
        </div>
        @include('content.organizer.raffles._progress', ['sold' => $paidTickets, 'total' => $raffle->total_numbers])

        <div class="info-container">
          <h5 class="pb-4 border-bottom mt-6 mb-4">Detalhes</h5>
          <ul class="list-unstyled mb-6">
            <li class="mb-2"><span class="h6 me-1">Números:</span><span>{{ $raffle->formatNumber($raffle->number_start) }} a {{ $raffle->formatNumber($raffle->lastNumber()) }}</span></li>
            <li class="mb-2"><span class="h6 me-1">Preço:</span><span>{{ $raffle->priceFormatted() }}</span></li>
            <li class="mb-2"><span class="h6 me-1">Vendas até:</span><span>{{ $raffle->sales_end_at?->format('d/m/Y H:i') ?? '—' }}</span></li>
            <li class="mb-2"><span class="h6 me-1">Sorteio:</span><span>{{ $raffle->draw_at?->format('d/m/Y H:i') ?? '—' }}</span></li>
            <li class="mb-2"><span class="h6 me-1">Método:</span><span>{{ $raffle->draw_method->label() }}</span></li>
            <li class="mb-2"><span class="h6 me-1">Não vendido:</span><span>{{ $raffle->unsold_rule->label() }}</span></li>
            <li class="mb-2"><span class="h6 me-1">Autorização:</span>
              <span>{{ $raffle->authorization_number ?: '—' }}
                @if ($raffle->authorization_doc_path)<a href="{{ route('painel.rifas.authorization', $raffle) }}" target="_blank"><i class="ti ti-paperclip"></i></a>@endif
              </span>
            </li>
            @if ($raffle->commit_hash)
              <li class="mb-2"><span class="h6 me-1">Commit:</span><code class="small text-break">{{ $raffle->commit_hash }}</code></li>
            @endif
          </ul>
          @can('cancel', $raffle)
            <div class="d-flex justify-content-center">
              <button class="btn btn-label-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelRaffleModal">Cancelar rifa</button>
            </div>
          @endcan
        </div>
      </div>
    </div>
  </div>

  <!-- Conteúdo -->
  <div class="col-xl-8 col-lg-7 col-md-7">
    <ul class="nav nav-pills flex-column flex-md-row mb-6 row-gap-2" role="tablist">
      @foreach (['geral' => ['ti-layout-dashboard', 'Visão geral'], 'pedidos' => ['ti-shopping-cart', 'Pedidos'], 'sorteio' => ['ti-trophy', 'Sorteio'], 'auditoria' => ['ti-shield-check', 'Auditoria']] as $key => [$icon, $label])
        <li class="nav-item"><button type="button" class="nav-link {{ $tab === $key ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}"><i class="ti {{ $icon }} ti-sm me-1_5"></i>{{ $label }}</button></li>
      @endforeach
    </ul>

    <div class="tab-content p-0 shadow-none bg-transparent">
      <!-- Visão geral -->
      <div class="tab-pane fade {{ $tab === 'geral' ? 'show active' : '' }}" id="tab-geral">
        @if ($raffle->isEditable())
          <div class="card mb-6">
            <div class="card-header"><h5 class="card-title mb-0">Checklist para envio</h5></div>
            <div class="card-body">
              @if (count($issues))
                <ul class="list-unstyled mb-0">
                  @foreach ($issues as $issue)
                    <li class="mb-2"><i class="ti ti-circle-x text-danger me-2"></i>{{ $issue }}</li>
                  @endforeach
                </ul>
              @else
                <p class="mb-0 text-success"><i class="ti ti-circle-check me-2"></i>Tudo pronto! Envie a rifa para análise.</p>
              @endif
            </div>
          </div>
        @endif
        @if ($raffle->review_notes)
          <div class="alert alert-info"><h6 class="alert-heading mb-1">Observações da análise</h6>{{ $raffle->review_notes }}</div>
        @endif

        <div class="row g-6 mb-6">
          @foreach ([
            ['Pedidos pagos', $orders['paid']->total ?? 0, 'ti-circle-check', 'success'],
            ['Aguardando Pix', $orders['awaiting_payment']->total ?? 0, 'ti-clock', 'warning'],
            ['Números reservados', $reserved, 'ti-lock', 'info'],
            ['Pedidos expirados', $orders['expired']->total ?? 0, 'ti-clock-x', 'secondary'],
          ] as [$label, $value, $icon, $color])
            <div class="col-sm-6">
              <div class="card card-border-shadow-{{ $color }} h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="ti {{ $icon }} ti-28px"></i></span></div>
                    <h4 class="mb-0">{{ $value }}</h4>
                  </div>
                  <p class="mb-0">{{ $label }}</p>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Financeiro</h5></div>
          <div class="table-responsive">
            <table class="table">
              <tbody>
                <tr><td>Arrecadação bruta</td><td class="text-end fw-medium">{{ money_br($revenue) }}</td></tr>
                <tr><td>Taxa da plataforma ({{ $fee }}%)</td><td class="text-end text-danger">− {{ money_br($platformFee) }}</td></tr>
                <tr><td class="fw-medium">Repasse ao organizador (split automático)</td><td class="text-end fw-bold text-success">{{ money_br($revenue - $platformFee) }}</td></tr>
                <tr><td>Reembolsado</td><td class="text-end">{{ money_br((int) ($orders['refunded']->amount ?? 0)) }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Pedidos -->
      <div class="tab-pane fade {{ $tab === 'pedidos' ? 'show active' : '' }}" id="tab-pedidos">
        <div class="card">
          <div class="card-datatable table-responsive">
            <form id="raffleOrderFilters"><input type="hidden" name="raffle" value="{{ $raffle->id }}"></form>
            <table class="table border-top saas-datatable"
              data-saas-url="{{ route('painel.pedidos.data') }}"
              data-saas-columns='["order","date","customer","raffle","quantity","total","status","actions"]'
              data-saas-orderable='[0,1,4,5,6]'
              data-saas-order='[[0,"desc"]]'
              data-saas-filter="#raffleOrderFilters"
              data-saas-search="Buscar pedido ou comprador">
              <thead><tr><th>Pedido</th><th>Data</th><th>Comprador</th><th>Rifa</th><th>Qtd</th><th>Total</th><th>Status</th><th></th></tr></thead>
            </table>
          </div>
        </div>
      </div>

      <!-- Sorteio -->
      <div class="tab-pane fade {{ $tab === 'sorteio' ? 'show active' : '' }}" id="tab-sorteio">
        @forelse ($raffle->draws as $draw)
          <div class="card mb-6">
            <div class="card-header d-flex justify-content-between">
              <h5 class="card-title mb-0">{{ $draw->executed_at->format('d/m/Y H:i') }} — {{ $draw->method->label() }}</h5>
              {!! $draw->outcome->badge() !!}
            </div>
            <div class="card-body">
              @if ($draw->drawn_number !== null)
                <p class="mb-2">Número contemplado: <strong class="h5">{{ $raffle->formatNumber($draw->drawn_number) }}</strong></p>
                <p class="mb-2">Ganhador: <strong>{{ $draw->customer?->name }}</strong> · {{ mask_phone($draw->customer?->phone) }} · {{ $draw->customer?->email }}</p>
              @endif
              <p class="mb-2 small">{{ $draw->raw_data['regra_aplicada'] ?? '' }}</p>
              <p class="mb-0 small">Hash: <code class="text-break">{{ $draw->hash }}</code></p>
            </div>
          </div>
        @empty
          <div class="alert alert-secondary">O sorteio ainda não foi realizado.</div>
        @endforelse

        @can('complete', $raffle)
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Registrar entrega do prêmio</h5></div>
            <div class="card-body">
              <form method="POST" action="{{ route('painel.rifas.delivery', $raffle) }}" enctype="multipart/form-data" class="row g-4">
                @csrf
                <div class="col-md-6">
                  <label class="form-label" for="proof">Comprovante (foto, recibo ou termo)</label>
                  <input type="file" class="form-control" id="proof" name="proof" accept="application/pdf,image/jpeg,image/png" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="notes">Observações</label>
                  <input type="text" class="form-control" id="notes" name="notes">
                </div>
                <div class="col-12"><button class="btn btn-success">Concluir rifa</button></div>
              </form>
            </div>
          </div>
        @endcan
        @if ($raffle->isPubliclyVisible() && $raffle->draws->isNotEmpty())
          <a href="{{ route('public.draw', [$raffle->tenant->slug, $raffle->slug]) }}" target="_blank" class="btn btn-label-primary mt-4"><i class="ti ti-certificate me-1"></i>Prova pública do sorteio</a>
        @endif
      </div>

      <!-- Auditoria -->
      <div class="tab-pane fade {{ $tab === 'auditoria' ? 'show active' : '' }}" id="tab-auditoria">
        <div class="card">
          <div class="card-body pt-6">
            <ul class="timeline mb-0">
              @forelse ($logs as $log)
                <li class="timeline-item timeline-item-transparent {{ $loop->last ? 'border-transparent' : '' }}">
                  <span class="timeline-point timeline-point-primary"></span>
                  <div class="timeline-event">
                    <div class="timeline-header mb-2">
                      <h6 class="mb-0">{{ $log->action }}</h6>
                      <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                    </div>
                    <p class="mb-1 small">{{ $log->user?->name ?? 'Sistema' }} @if($log->ip) · {{ $log->ip }} @endif</p>
                    @if ($log->after)<small class="text-muted text-break">{{ json_encode($log->after, JSON_UNESCAPED_UNICODE) }}</small>@endif
                  </div>
                </li>
              @empty
                <li class="text-muted">Sem registros.</li>
              @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@can('cancel', $raffle)
<!-- Modal cancelar (base: _partials/_modals) -->
<div class="modal fade" id="cancelRaffleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('painel.rifas.cancel', $raffle) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Cancelar rifa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          @if (in_array($raffle->status, [S::Published, S::SalesClosed]))
            <div class="alert alert-warning">Todos os pagamentos ({{ money_br($revenue) }}) serão reembolsados integralmente aos compradores.</div>
          @endif
          <label class="form-label" for="reason">Motivo</label>
          <textarea class="form-control" id="reason" name="reason" rows="3" minlength="10" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Voltar</button>
          <button type="submit" class="btn btn-danger">Cancelar rifa</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan
@endsection
