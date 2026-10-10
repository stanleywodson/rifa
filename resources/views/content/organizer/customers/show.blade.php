@extends('layouts/layoutMaster')

@section('title', $customer->name)

{{-- Base: apps/app-ecommerce-customer-details-overview --}}
@section('content')
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-sm-between mb-6 text-center text-sm-start gap-2">
  <div class="mb-2 mb-sm-0">
    <h4 class="mb-1">{{ $customer->name }}</h4>
    <p class="mb-0">Comprador desde {{ $customer->created_at->format('d/m/Y') }}</p>
  </div>
</div>

<div class="row">
  <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
    <div class="card mb-6">
      <div class="card-body pt-12">
        <div class="d-flex align-items-center flex-column">
          <div class="avatar avatar-xl mb-4"><span class="avatar-initial rounded bg-label-primary">{{ mb_substr($customer->name, 0, 1) }}</span></div>
          <div class="customer-info text-center mb-6">
            <h5 class="mb-0">{{ $customer->name }}</h5>
            <span>CPF {{ $customer->maskedCpf() }}</span>
          </div>
        </div>
        <div class="d-flex justify-content-around flex-wrap mb-6 gap-0 gap-md-3 gap-lg-4">
          <div class="d-flex align-items-center gap-4 me-5">
            <div class="avatar"><div class="avatar-initial rounded bg-label-primary"><i class='ti ti-ticket ti-lg'></i></div></div>
            <div><h5 class="mb-0">{{ $numbers }}</h5><span>Números</span></div>
          </div>
          <div class="d-flex align-items-center gap-4">
            <div class="avatar"><div class="avatar-initial rounded bg-label-primary"><i class='ti ti-currency-real ti-lg'></i></div></div>
            <div><h5 class="mb-0">{{ money_br($spent) }}</h5><span>Gasto</span></div>
          </div>
        </div>
        <div class="info-container">
          <h5 class="pb-4 border-bottom text-capitalize mt-6 mb-4">Contato</h5>
          <ul class="list-unstyled mb-6">
            <li class="mb-2"><span class="h6 me-1">E-mail:</span><span>{{ $customer->email ?: '—' }}</span></li>
            <li class="mb-2"><span class="h6 me-1">WhatsApp:</span><span>{{ $customer->phone }}</span></li>
          </ul>
          <a href="https://wa.me/55{{ $customer->phone }}" target="_blank" rel="noopener" class="btn btn-label-success w-100"><i class="ti ti-brand-whatsapp me-1"></i>Conversar</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8 col-lg-7 col-md-7">
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title mb-0">Pedidos</h5></div>
      <div class="table-responsive">
        <table class="table">
          <thead class="border-top"><tr><th>Pedido</th><th>Rifa</th><th>Números</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
            @foreach ($orders as $order)
              <tr>
                <td><a href="{{ route('painel.pedidos.show', $order) }}">#{{ strtoupper(substr($order->code, -8)) }}</a><br><small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small></td>
                <td>{{ $order->raffle->title }}</td>
                <td class="small">{{ implode(', ', $order->numbersFormatted()) }}</td>
                <td class="text-nowrap">{{ money_br($order->total_cents) }}</td>
                <td>{!! $order->status->badge() !!}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
