@php use App\Domain\Orders\Enums\OrderStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Pedidos')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'
])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-datatable.js'])
@endsection

{{-- Base: apps/app-ecommerce-order-list --}}
@section('content')
@include('_partials.saas.stat-strip', ['items' => [
  ['value' => $counts['awaiting_payment'] ?? 0, 'label' => 'Aguardando pagamento', 'icon' => 'ti-calendar-stats'],
  ['value' => $counts['paid'] ?? 0, 'label' => 'Pagos', 'icon' => 'ti-checks'],
  ['value' => $counts['refunded'] ?? 0, 'label' => 'Reembolsados', 'icon' => 'ti-wallet'],
  ['value' => $counts['expired'] ?? 0, 'label' => 'Expirados', 'icon' => 'ti-alert-octagon'],
]])

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-4">Filtros</h5>
    <form id="orderFilters" class="row g-4">
      <div class="col-md-6">
        <select name="raffle" class="form-select">
          <option value="">Todas as rifas</option>
          @foreach ($raffles as $id => $title)
            <option value="{{ $id }}" @selected($raffleId === $id)>{{ $title }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6">
        <select name="status" class="form-select">
          <option value="">Todos os status</option>
          @foreach (OrderStatus::cases() as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
          @endforeach
        </select>
      </div>
    </form>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('painel.pedidos.data') }}"
      data-saas-columns='["order","date","customer","raffle","quantity","total","status","actions"]'
      data-saas-orderable='[0,1,4,5,6]'
      data-saas-order='[[0,"desc"]]'
      data-saas-filter="#orderFilters"
      data-saas-search="Buscar pedido, nome ou telefone">
      <thead><tr><th>Pedido</th><th>Data</th><th>Comprador</th><th>Rifa</th><th>Qtd</th><th>Total</th><th>Status</th><th>Ações</th></tr></thead>
    </table>
  </div>
</div>
@endsection
