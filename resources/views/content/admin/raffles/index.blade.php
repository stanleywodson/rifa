@php use App\Domain\Raffles\Enums\RaffleStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Rifas — moderação')

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

{{-- Base: apps/app-ecommerce-manage-reviews --}}
@section('content')
@include('_partials.saas.stat-strip', ['items' => [
  ['value' => $counts['pending_review'] ?? 0, 'label' => 'Em análise', 'icon' => 'ti-hourglass', 'color' => 'warning'],
  ['value' => $counts['changes_requested'] ?? 0, 'label' => 'Ajustes solicitados', 'icon' => 'ti-message-2', 'color' => 'info'],
  ['value' => $counts['published'] ?? 0, 'label' => 'Publicadas', 'icon' => 'ti-world', 'color' => 'success'],
  ['value' => $counts['rejected'] ?? 0, 'label' => 'Reprovadas', 'icon' => 'ti-ban', 'color' => 'danger'],
]])

<div class="card">
  <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-4">
    <h5 class="card-title mb-0">Rifas</h5>
    <form id="adminRaffleFilters">
      <select name="status" class="form-select">
        <option value="">Todos os status</option>
        @foreach (RaffleStatus::cases() as $s)
          <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
        @endforeach
      </select>
    </form>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('admin.rifas.data') }}"
      data-saas-columns='["raffle","tenant","status","numbers","price","submitted","actions"]'
      data-saas-orderable='[0,2,3,4,5]'
      data-saas-order='[]'
      data-saas-filter="#adminRaffleFilters"
      data-saas-search="Buscar rifa ou organizador">
      <thead><tr><th>Rifa</th><th>Organizador</th><th>Status</th><th>Números</th><th>Preço</th><th>Enviada em</th><th>Ações</th></tr></thead>
    </table>
  </div>
</div>
@endsection
