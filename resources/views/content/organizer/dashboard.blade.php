@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-charts.js'])
@endsection

{{-- Base: apps/app-ecommerce-dashboard --}}
@section('content')
@if (!$tenant->canReceivePayments())
  <div class="alert alert-danger d-flex align-items-center mb-6">
    <span class="alert-icon rounded"><i class="ti ti-building-bank"></i></span>
    <span class="flex-grow-1">{{ $tenant->hasStalePaymentAccount() ? 'Sua conta de recebimento foi criada em outro ambiente e não é válida.' : 'Configure a conta de recebimento.' }} Enquanto isso, as vendas das suas rifas ficam bloqueadas.</span>
    <a href="{{ route('painel.configuracoes.edit', ['aba' => 'recebimentos']) }}" class="btn btn-sm btn-danger">Configurar recebimentos</a>
  </div>
@endif
@if (!$tenant->isVerified())
  <div class="alert alert-warning d-flex align-items-center mb-6">
    <span class="alert-icon rounded"><i class="ti ti-id-badge-2"></i></span>
    <span class="flex-grow-1">Para publicar rifas, envie os documentos de verificação da organização ({{ $tenant->kyc_status->label() }}).</span>
    <a href="{{ route('painel.configuracoes.edit', ['aba' => 'verificacao']) }}" class="btn btn-sm btn-warning">Enviar documentos</a>
  </div>
@endif

<div class="row g-6">
  <div class="col-xl-4">
    <div class="card">
      <div class="d-flex align-items-end row">
        <div class="col-7">
          <div class="card-body text-nowrap">
            <h5 class="card-title mb-0">Olá, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}! 🎉</h5>
            <p class="mb-2">Arrecadado nos últimos 30 dias</p>
            <h4 class="text-primary mb-1">{{ money_br($revenue) }}</h4>
            <a href="{{ route('painel.rifas.create') }}" class="btn btn-primary">Nova rifa</a>
          </div>
        </div>
        <div class="col-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="{{ asset('assets/img/illustrations/card-advance-sale.png') }}" height="140" alt="">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8 col-md-12">
    @include('_partials.saas.statistics', [
      'title' => 'Indicadores',
      'subtitle' => 'Últimos 30 dias',
      'items' => [
        ['value' => number_format($soldNumbers, 0, ',', '.'), 'label' => 'Números vendidos', 'icon' => 'ti-ticket', 'color' => 'primary'],
        ['value' => $paidCount, 'label' => 'Pedidos pagos', 'icon' => 'ti-shopping-cart', 'color' => 'info'],
        ['value' => $conversion . '%', 'label' => 'Conversão do checkout', 'icon' => 'ti-chart-pie-2', 'color' => 'success'],
        ['value' => $expiredRate . '%', 'label' => 'Reservas expiradas', 'icon' => 'ti-clock-x', 'color' => 'danger'],
      ],
    ])
  </div>

  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div>
          <h5 class="card-title mb-0">Vendas por dia</h5>
          <small class="text-muted">Total histórico: {{ money_br($revenueAll) }} · taxa da plataforma {{ $fee }}%</small>
        </div>
      </div>
      <div class="card-body">
        <div data-apex-area='@json(['labels' => $chart['labels'], 'series' => $chart['series'], 'name' => 'Vendas'])'></div>
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h5 class="card-title m-0 me-2">Últimas vendas</h5>
        <a href="{{ route('painel.pedidos.index') }}" class="small">Ver todas</a>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0">
          @forelse ($recentOrders as $order)
            <li class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-4"><span class="avatar-initial rounded bg-label-success"><i class="ti ti-receipt"></i></span></div>
              <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                <div class="me-2">
                  <h6 class="mb-0"><a href="{{ route('painel.pedidos.show', $order) }}" class="text-heading">{{ $order->customer->name }}</a></h6>
                  <small class="text-body d-block">{{ $order->quantity }} nº · {{ \Illuminate\Support\Str::limit($order->raffle->title, 24) }}</small>
                </div>
                <div class="user-progress d-flex align-items-center gap-1">
                  <h6 class="mb-0 text-success">{{ money_br($order->total_cents) }}</h6>
                </div>
              </div>
            </li>
          @empty
            <li class="text-muted">Nenhuma venda ainda.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Rifas em andamento</h5>
        <a href="{{ route('painel.rifas.index') }}" class="btn btn-sm btn-label-primary">Todas as rifas</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead class="border-top">
            <tr><th>Rifa</th><th>Status</th><th>Vendidos</th><th>Sorteio</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($raffles as $raffle)
              <tr>
                <td>@include('content.organizer.raffles._cell', ['raffle' => $raffle])</td>
                <td>{!! $raffle->status->badge() !!}</td>
                <td style="min-width: 180px">@include('content.organizer.raffles._progress', ['sold' => $raffle->sold_count, 'total' => $raffle->total_numbers])</td>
                <td>{{ $raffle->draw_at?->format('d/m/Y') }}</td>
                <td><a href="{{ route('painel.rifas.show', $raffle) }}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill"><i class="ti ti-eye"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-6">Nenhuma rifa ativa. <a href="{{ route('painel.rifas.create') }}">Crie a primeira</a>.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
