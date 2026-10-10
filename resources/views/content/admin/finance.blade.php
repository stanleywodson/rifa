@php use App\Domain\Payments\Enums\PaymentStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Financeiro')

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

{{-- Base: apps/app-invoice-list --}}
@section('content')
@include('_partials.saas.stat-strip', ['items' => [
  ['value' => money_br($gross), 'label' => 'Recebido via Pix', 'icon' => 'ti-cash', 'color' => 'success'],
  ['value' => $count, 'label' => 'Pagamentos', 'icon' => 'ti-receipt', 'color' => 'primary'],
  ['value' => $pending, 'label' => 'Pix pendentes', 'icon' => 'ti-clock', 'color' => 'warning'],
  ['value' => money_br($refunded), 'label' => 'Estornado', 'icon' => 'ti-receipt-refund', 'color' => 'info'],
]])

@if ($failedRefunds->isNotEmpty())
  <div class="card mb-6 border border-danger">
    <div class="card-header"><h5 class="card-title mb-0 text-danger"><i class="ti ti-alert-triangle me-1"></i>Estornos com falha — tratar manualmente</h5></div>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Pagamento</th><th>Pedido</th><th>Valor</th><th>Motivo</th><th>Erro</th></tr></thead>
        <tbody>
          @foreach ($failedRefunds as $refund)
            <tr>
              <td><code>{{ $refund->payment->gateway_id }}</code></td>
              <td>{{ $refund->payment->order?->code }}</td>
              <td>{{ money_br($refund->amount_cents) }}</td>
              <td>{{ $refund->reason }}</td>
              <td class="small text-danger">{{ $refund->payload['error'] ?? '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

<div class="card">
  <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-4">
    <h5 class="card-title mb-0">Transações</h5>
    <form id="financeFilters">
      <select name="status" class="form-select">
        <option value="">Todos os status</option>
        @foreach (PaymentStatus::cases() as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
      </select>
    </form>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('admin.financeiro.data') }}"
      data-saas-columns='["id","tenant","raffle","customer","amount","fee","status","date"]'
      data-saas-orderable='[0,4,6,7]'
      data-saas-order='[[0,"desc"]]'
      data-saas-filter="#financeFilters"
      data-saas-search="Buscar cobrança ou pedido">
      <thead><tr><th>Cobrança</th><th>Organizador</th><th>Rifa</th><th>Comprador</th><th>Valor</th><th>Taxa</th><th>Status</th><th>Data</th></tr></thead>
    </table>
  </div>
</div>
@endsection
