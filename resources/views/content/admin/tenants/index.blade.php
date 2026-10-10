@php use App\Domain\Tenants\Enums\KycStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Organizadores')

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

{{-- Base: apps/app-user-list --}}
@section('content')
@include('_partials.saas.stat-strip', ['items' => [
  ['value' => $total, 'label' => 'Organizadores', 'icon' => 'ti-building-store', 'color' => 'primary'],
  ['value' => $kyc['submitted'] ?? 0, 'label' => 'KYC em análise', 'icon' => 'ti-id-badge-2', 'color' => 'warning'],
  ['value' => $kyc['approved'] ?? 0, 'label' => 'Verificados', 'icon' => 'ti-rosette-discount-check', 'color' => 'success'],
  ['value' => $suspended, 'label' => 'Suspensos', 'icon' => 'ti-ban', 'color' => 'danger'],
]])

<div class="card">
  <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-4">
    <h5 class="card-title mb-0">Organizadores</h5>
    <form id="tenantFilters">
      <select name="kyc" class="form-select">
        <option value="">Todos os KYC</option>
        @foreach (KycStatus::cases() as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
      </select>
    </form>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('admin.organizadores.data') }}"
      data-saas-columns='["tenant","plan","kyc","raffles","status","since","actions"]'
      data-saas-orderable='[0,3,5]'
      data-saas-order='[[5,"desc"]]'
      data-saas-filter="#tenantFilters"
      data-saas-search="Buscar organizador">
      <thead><tr><th>Organizador</th><th>Plano</th><th>KYC</th><th>Rifas</th><th>Status</th><th>Cadastro</th><th>Ações</th></tr></thead>
    </table>
  </div>
</div>
@endsection
