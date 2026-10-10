@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Meus números')

@section('page-meta')
<meta name="robots" content="noindex">
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js'])
@endsection

@section('content')
<section class="section-py bg-body first-section-pt">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card mb-6">
          <div class="card-body p-md-8">
            <h4 class="mb-1">Consultar meus números</h4>
            <p class="mb-6">Informe seu CPF ou WhatsApp e o código de qualquer pedido seu (enviado por e-mail e exibido na página do pedido).</p>
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            <form method="POST" action="{{ route('public.my-tickets.lookup') }}" class="row g-4">
              @csrf
              <div class="col-md-5">
                <label class="form-label" for="document">CPF ou WhatsApp</label>
                <input type="text" id="document" name="document" value="{{ old('document') }}" class="form-control @error('document') is-invalid @enderror" required>
                @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-5">
                <label class="form-label" for="code">Código do pedido</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" maxlength="26" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="ti ti-search"></i></button>
              </div>
            </form>
          </div>
        </div>

        @if ($orders !== null)
          <h5 class="mb-4">Olá, {{ $customer->name }} — {{ $orders->count() }} pedido(s)</h5>
          @forelse ($orders as $order)
            <div class="card mb-4">
              <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                  <div>
                    <h6 class="mb-0"><a href="{{ route('public.raffle', [$order->raffle->tenant->slug, $order->raffle->slug]) }}">{{ $order->raffle->title }}</a></h6>
                    <small class="text-muted">{{ $order->raffle->tenant->name }} · {{ $order->created_at->format('d/m/Y H:i') }}</small>
                  </div>
                  <div>{!! $order->status->badge() !!}</div>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                  @foreach ($order->items as $item)
                    @php $won = $order->raffle->lastDraw?->drawn_number === $item->number && $order->status->value === 'paid'; @endphp
                    <span class="badge {{ $won ? 'bg-success' : 'bg-label-primary' }}">{{ $order->raffle->formatNumber($item->number) }}@if($won) 🏆@endif</span>
                  @endforeach
                </div>
                <a href="{{ route('public.order', $order->code) }}" class="btn btn-sm btn-label-primary">Ver pedido</a>
              </div>
            </div>
          @empty
            <div class="alert alert-secondary">Nenhum pedido pago ou pendente.</div>
          @endforelse
        @endif
      </div>
    </div>
  </div>
</section>
@endsection
