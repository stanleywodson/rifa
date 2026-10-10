@php
$configData = Helper::appClasses();
$tenant = $raffle->tenant;
$payment = $order->payment;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Pedido ' . strtoupper(substr($order->code, -8)))

@section('page-meta')
<meta name="robots" content="noindex">
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-order.js'])
@endsection

{{-- Base: front-pages/payment-page (Pix) e apps/app-invoice-preview (comprovante) --}}
@section('content')
<section class="section-py bg-body first-section-pt">
  <div class="container" id="orderPage"
    data-status-url="{{ route('public.order.status', $order->code) }}"
    data-status="{{ $order->status->value }}"
    data-expires="{{ $order->expires_at->toIso8601String() }}">
    <div class="row g-6 justify-content-center">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body p-md-8">
            <div class="d-flex flex-wrap justify-content-between gap-4 mb-6">
              <div>
                <h4 class="mb-1">Pedido #{{ strtoupper(substr($order->code, -8)) }}</h4>
                <p class="mb-0">{{ $raffle->title }} · {{ $tenant->name }}</p>
              </div>
              <div class="text-end">{!! $order->status->badge() !!}<br><small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small></div>
            </div>

            @if ($order->isAwaitingPayment() && $payment)
              <div class="row g-6 align-items-center">
                <div class="col-md-5 text-center">
                  @if ($payment->pix_qr_image)
                    <img src="data:image/png;base64,{{ $payment->pix_qr_image }}" alt="QR Code Pix" class="img-fluid border rounded p-2" style="max-width: 260px">
                  @else
                    <div class="avatar avatar-xl mx-auto"><span class="avatar-initial rounded bg-label-primary"><i class="ti ti-qrcode ti-xl"></i></span></div>
                  @endif
                </div>
                <div class="col-md-7">
                  <h5 class="mb-1">Pague {{ money_br($order->total_cents) }} com Pix</h5>
                  <p class="mb-4">Abra o app do seu banco, escolha Pix e escaneie o QR Code ou use o código copia e cola.</p>
                  <label class="form-label" for="pixCode">Pix copia e cola</label>
                  <div class="input-group mb-4">
                    <input type="text" class="form-control" id="pixCode" value="{{ $payment->pix_copy_paste }}" readonly>
                    <button class="btn btn-primary" type="button" data-copy-target="#pixCode"><i class="ti ti-copy me-1"></i>Copiar</button>
                  </div>
                  <div class="alert alert-warning d-flex align-items-center mb-0">
                    <i class="ti ti-clock me-2"></i>
                    <span>Reserva expira em <strong data-countdown>--:--</strong>. Esta página atualiza sozinha após o pagamento.</span>
                  </div>
                  @if ($canSimulate)
                    <form method="POST" action="{{ route('public.order.simulate', $order->code) }}" class="mt-4">
                      @csrf
                      <button class="btn btn-outline-success w-100"><i class="ti ti-flask me-1"></i>Simular pagamento (ambiente local)</button>
                    </form>
                  @endif
                </div>
              </div>
            @elseif ($order->status->value === 'paid')
              <div class="alert alert-success d-flex align-items-center">
                <span class="alert-icon rounded"><i class="ti ti-check"></i></span>
                <span>Pagamento confirmado em {{ $order->paid_at?->format('d/m/Y H:i') }}. Boa sorte!</span>
              </div>
            @elseif ($order->status->value === 'expired')
              <div class="alert alert-secondary">
                A reserva expirou sem pagamento e os números foram liberados.
                <a href="{{ route('public.raffle', [$tenant->slug, $raffle->slug]) }}">Escolher novamente</a>.
              </div>
            @else
              <div class="alert alert-info">{{ $order->status->label() }}.</div>
            @endif

            <hr class="my-6">
            <div class="row g-6">
              <div class="col-sm-6">
                <h6 class="mb-2">Comprador</h6>
                <p class="mb-1">{{ $order->customer->name }}</p>
                <p class="mb-1">CPF {{ $order->customer->maskedCpf() }}</p>
                <p class="mb-0">{{ mask_phone($order->customer->phone) }}</p>
              </div>
              <div class="col-sm-6">
                <h6 class="mb-2">Sorteio</h6>
                <p class="mb-1">{{ $raffle->draw_method->label() }}</p>
                <p class="mb-0">{{ $raffle->draw_at?->format('d/m/Y H:i') ?? 'A definir' }}</p>
              </div>
            </div>
            <div class="table-responsive border border-bottom-0 rounded mt-6">
              <table class="table m-0">
                <thead><tr><th>Números</th><th class="text-end">Qtd</th><th class="text-end">Valor</th></tr></thead>
                <tbody>
                  <tr>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        @foreach ($order->numbersFormatted() as $n)
                          <span class="badge {{ $order->status->value === 'paid' ? 'bg-label-success' : 'bg-label-primary' }}">{{ $n }}</span>
                        @endforeach
                      </div>
                    </td>
                    <td class="text-end">{{ $order->quantity }}</td>
                    <td class="text-end text-nowrap">{{ money_br($order->total_cents) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between flex-wrap gap-2 mt-6">
              <small class="text-muted">Código do pedido: <code>{{ $order->code }}</code> — guarde para consultar seus números.</small>
              <div class="d-flex gap-2">
                <a href="{{ route('public.raffle', [$tenant->slug, $raffle->slug]) }}" class="btn btn-label-secondary btn-sm">Ver rifa</a>
                @if ($order->status->value === 'paid')<button onclick="window.print()" class="btn btn-label-primary btn-sm"><i class="ti ti-printer me-1"></i>Imprimir</button>@endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
