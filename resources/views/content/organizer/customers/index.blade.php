@extends('layouts/layoutMaster')

@section('title', 'Compradores')

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

{{-- Base: apps/app-ecommerce-customer-all --}}
@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Compradores <span class="text-muted fw-normal">({{ $total }})</span></h5>
    <small class="text-muted">CPF e telefone exibidos mascarados (LGPD)</small>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('painel.compradores.data') }}"
      data-saas-columns='["customer","cpf","orders","spent","since","actions"]'
      data-saas-orderable='[0,2,3,4]'
      data-saas-order='[[4,"desc"]]'
      data-saas-search="Buscar por nome, e-mail ou telefone">
      <thead><tr><th>Comprador</th><th>CPF</th><th>Pedidos pagos</th><th>Total gasto</th><th>Desde</th><th>Ações</th></tr></thead>
    </table>
  </div>
</div>
@endsection
