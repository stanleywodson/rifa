@extends('layouts/layoutMaster')

@section('title', 'Pedido #' . strtoupper(substr($order->code, -8)))

{{-- Base: apps/app-ecommerce-order-details --}}
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
  <div class="d-flex flex-column justify-content-center">
    <div class="mb-1">
      <span class="h5">Pedido #{{ strtoupper(substr($order->code, -8)) }}</span> <span class="ms-2">{!! $order->status->badge() !!}</span>
    </div>
    <p class="mb-0">{{ $order->created_at->format('d/m/Y H:i') }} · <a href="{{ route('painel.rifas.show', $order->raffle) }}">{{ $order->raffle->title }}</a></p>
  </div>
  <div class="d-flex align-content-center flex-wrap gap-2">
    <a href="{{ route('public.order', $order->code) }}" target="_blank" class="btn btn-label-primary"><i class="ti ti-external-link me-1"></i>Página do comprador</a>
  </div>
</div>

<div class="row">
  <div class="col-12 col-lg-8">
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Números do pedido</h5></div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-6">
          @foreach ($order->items as $item)
            <span class="badge bg-label-{{ $order->status->value === 'paid' ? 'success' : 'secondary' }} fs-6">{{ $order->raffle->formatNumber($item->number) }}</span>
          @endforeach
        </div>
        <div class="d-flex justify-content-end align-items-center">
          <div class="order-calculations">
            <div class="d-flex justify-content-start mb-2"><span class="w-px-150 text-heading">Quantidade:</span><h6 class="mb-0">{{ $order->quantity }} × {{ $order->raffle->priceFormatted() }}</h6></div>
            <div class="d-flex justify-content-start mb-2"><span class="w-px-150 text-heading">Subtotal:</span><h6 class="mb-0">{{ money_br($order->subtotal_cents) }}</h6></div>
            <div class="d-flex justify-content-start mb-2"><span class="w-px-150 text-heading">Desconto:</span><h6 class="mb-0">{{ money_br($order->discount_cents) }}</h6></div>
            <div class="d-flex justify-content-start"><h6 class="w-px-150 mb-0">Total:</h6><h6 class="mb-0">{{ money_br($order->total_cents) }}</h6></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Pagamentos</h5></div>
      <div class="card-body pt-1">
        <ul class="timeline pb-0 mb-0">
          <li class="timeline-item timeline-item-transparent border-primary">
            <span class="timeline-point timeline-point-primary"></span>
            <div class="timeline-event">
              <div class="timeline-header"><h6 class="mb-0">Pedido criado</h6><small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small></div>
              <p class="mt-1 mb-0">Reserva válida até {{ $order->expires_at->format('d/m/Y H:i') }}</p>
            </div>
          </li>
          @foreach ($order->payments as $payment)
            <li class="timeline-item timeline-item-transparent {{ $loop->last ? 'border-transparent pb-0' : 'border-primary' }}">
              <span class="timeline-point timeline-point-{{ $payment->status->color() }}"></span>
              <div class="timeline-event pb-0">
                <div class="timeline-header"><h6 class="mb-0">Pix {{ $payment->status->label() }} — {{ money_br($payment->amount_cents) }}</h6><small class="text-muted">{{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}</small></div>
                <p class="mt-1 mb-1 small">Gateway {{ $payment->gateway }} · <code>{{ $payment->gateway_id }}</code></p>
                @foreach ($payment->splits as $split)
                  <span class="badge bg-label-secondary me-1">{{ $split->recipient === 'platform' ? 'Taxa plataforma' : 'Organizador' }}: {{ money_br($split->amount_cents) }}</span>
                @endforeach
                @foreach ($payment->refunds as $refund)
                  <div class="alert alert-info small mt-2 mb-0">Estorno {{ $refund->status }}: {{ money_br($refund->amount_cents) }} — {{ $refund->reason }}</div>
                @endforeach
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title m-0">Comprador</h5></div>
      <div class="card-body">
        <div class="d-flex justify-content-start align-items-center mb-6">
          <div class="avatar me-3"><span class="avatar-initial rounded-circle bg-label-primary">{{ mb_substr($order->customer->name, 0, 1) }}</span></div>
          <div class="d-flex flex-column">
            <a href="{{ route('painel.compradores.show', $order->customer) }}" class="text-body text-nowrap"><h6 class="mb-0">{{ $order->customer->name }}</h6></a>
            <span>CPF {{ $order->customer->maskedCpf() }}</span>
          </div>
        </div>
        <div class="d-flex justify-content-between"><h6 class="mb-1">Contato</h6></div>
        <p class="mb-1">E-mail: {{ $order->customer->email ?: '—' }}</p>
        <p class="mb-0">WhatsApp: {{ $order->customer->phone }}</p>
        <a href="https://wa.me/55{{ $order->customer->phone }}" target="_blank" rel="noopener" class="btn btn-sm btn-label-success mt-4"><i class="ti ti-brand-whatsapp me-1"></i>Conversar</a>
      </div>
    </div>
  </div>
</div>
@endsection
