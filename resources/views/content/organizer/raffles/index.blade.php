@php use App\Domain\Raffles\Enums\RaffleStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Minhas rifas')

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

{{-- Base: apps/app-ecommerce-product-list --}}
@section('content')
@include('_partials.saas.stat-strip', ['items' => [
  ['value' => ($counts['published'] ?? 0), 'label' => 'Publicadas', 'icon' => 'ti-world', 'color' => 'success'],
  ['value' => ($counts['pending_review'] ?? 0) + ($counts['changes_requested'] ?? 0), 'label' => 'Em análise / ajustes', 'icon' => 'ti-hourglass', 'color' => 'warning'],
  ['value' => ($counts['sales_closed'] ?? 0), 'label' => 'Aguardando sorteio', 'icon' => 'ti-calendar-event', 'color' => 'primary'],
  ['value' => ($counts['drawn'] ?? 0) + ($counts['completed'] ?? 0), 'label' => 'Sorteadas', 'icon' => 'ti-trophy', 'color' => 'info'],
]])

<div class="card">
  <div class="card-header border-bottom">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
      <h5 class="card-title mb-0">Rifas</h5>
      <form id="raffleFilters" class="d-flex gap-4">
        <select name="status" class="form-select">
          <option value="">Todos os status</option>
          @foreach (RaffleStatus::cases() as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
          @endforeach
        </select>
      </form>
      @if ($canCreate)
        <a href="{{ route('painel.rifas.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Nova rifa</a>
      @endif
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top saas-datatable"
      data-saas-url="{{ route('painel.rifas.data') }}"
      data-saas-columns='["raffle","status","progress","price","draw_at","actions"]'
      data-saas-orderable='[0,1,3,4]'
      data-saas-order='[]'
      data-saas-filter="#raffleFilters"
      data-saas-search="Buscar rifa">
      <thead>
        <tr><th>Rifa</th><th>Status</th><th>Vendidos</th><th>Preço</th><th>Sorteio</th><th>Ações</th></tr>
      </thead>
    </table>
  </div>
</div>
@endsection
