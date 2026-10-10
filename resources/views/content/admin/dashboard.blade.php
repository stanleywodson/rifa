@extends('layouts/layoutMaster')

@section('title', 'Administração')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-charts.js'])
@endsection

{{-- Base: dashboard/dashboards-crm + apps/app-ecommerce-dashboard --}}
@section('content')
<div class="row g-6">
  <div class="col-12">
    @include('_partials.saas.statistics', [
      'title' => 'Plataforma',
      'items' => [
        ['value' => money_br($gmv), 'label' => 'GMV total', 'icon' => 'ti-chart-pie-2', 'color' => 'primary'],
        ['value' => money_br($fees), 'label' => 'Receita de taxas', 'icon' => 'ti-currency-real', 'color' => 'success'],
        ['value' => $tenants, 'label' => 'Organizadores', 'icon' => 'ti-building-store', 'color' => 'info'],
        ['value' => $activeRaffles, 'label' => 'Rifas publicadas', 'icon' => 'ti-ticket', 'color' => 'warning'],
      ],
    ])
  </div>

  @foreach ([
    ['Rifas aguardando análise', $pendingRaffles, 'ti-hourglass', 'warning', route('admin.rifas.index')],
    ['KYC aguardando análise', $kycPending, 'ti-id-badge-2', 'info', route('admin.organizadores.index')],
    ['Estornos com falha', $failedRefunds, 'ti-alert-triangle', 'danger', route('admin.financeiro.index')],
  ] as [$label, $value, $icon, $color, $url])
    <div class="col-md-4">
      <a href="{{ $url }}" class="card card-border-shadow-{{ $color }} h-100 text-body">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-4"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="ti {{ $icon }} ti-28px"></i></span></div>
            <h4 class="mb-0">{{ $value }}</h4>
          </div>
          <p class="mb-0">{{ $label }}</p>
        </div>
      </a>
    </div>
  @endforeach

  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header"><h5 class="card-title mb-0">GMV últimos 30 dias</h5><small class="text-muted">{{ money_br($gmv30) }}</small></div>
      <div class="card-body"><div data-apex-area='@json(['labels' => $chart['labels'], 'series' => $chart['series'], 'name' => 'GMV'])'></div></div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h5 class="card-title mb-0">Fila de aprovação</h5>
        <a href="{{ route('admin.rifas.index') }}" class="small">Ver fila</a>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0">
          @forelse ($queue as $raffle)
            <li class="d-flex align-items-center mb-4">
              <div class="avatar flex-shrink-0 me-4"><span class="avatar-initial rounded bg-label-warning"><i class="ti ti-ticket"></i></span></div>
              <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                <div class="me-2">
                  <h6 class="mb-0"><a href="{{ route('admin.rifas.show', $raffle) }}" class="text-heading">{{ \Illuminate\Support\Str::limit($raffle->title, 28) }}</a></h6>
                  <small>{{ $raffle->tenant->name }}</small>
                </div>
                <small class="text-muted">{{ $raffle->submitted_at?->diffForHumans() }}</small>
              </div>
            </li>
          @empty
            <li class="text-muted">Nenhuma rifa aguardando.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
