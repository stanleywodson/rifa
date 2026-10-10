@extends('layouts/layoutMaster')

@section('title', 'Auditoria')

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

{{-- Base: tables/tables-datatables-advanced --}}
@section('content')
@if ($broken)
  <div class="alert alert-danger d-flex align-items-center mb-6"><span class="alert-icon rounded"><i class="ti ti-alert-octagon"></i></span>
    <span>Cadeia de auditoria <strong>adulterada</strong> a partir do registro #{{ $broken }}.</span></div>
@else
  <div class="alert alert-success d-flex align-items-center mb-6"><span class="alert-icon rounded"><i class="ti ti-shield-check"></i></span>
    <span>Cadeia de auditoria íntegra — {{ number_format($total, 0, ',', '.') }} registros verificados (hash SHA-256 encadeado).</span></div>
@endif
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('admin.auditoria.data') }}"
      data-saas-columns='["id","date","user","action","subject","changes","hash"]'
      data-saas-orderable='[0,1,3]'
      data-saas-order='[[0,"desc"]]'
      data-saas-search="Buscar ação, entidade ou IP">
      <thead><tr><th>#</th><th>Data</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>Dados</th><th>Hash</th></tr></thead>
    </table>
  </div>
</div>
@endsection
