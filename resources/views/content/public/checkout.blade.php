@php
$configData = Helper::appClasses();
$total = $quantity * $raffle->price_cents;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Participar — ' . $raffle->title)

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/front-page-payment.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js'])
@endsection

{{-- Base: front-pages/checkout-page e payment-page --}}
@section('content')
<section class="section-py bg-body first-section-pt">
  <div class="container">
    <div class="card px-3">
      <div class="row">
        <div class="col-lg-7 card-body border-end p-md-8">
          <h4 class="mb-2">Seus dados</h4>
          <p class="mb-6">Usados para identificar seus números e enviar o comprovante.</p>

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0 ps-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
          @endif

          <form id="checkoutForm" action="{{ route('public.checkout.store', [$tenant, $raffle]) }}" method="POST">
            @csrf
            @if ($numbers->isNotEmpty())
              <input type="hidden" name="numbers" value="{{ $numbers->implode(',') }}">
            @else
              <input type="hidden" name="quantity" value="{{ $quantity }}">
            @endif
            <div class="row g-6">
              <div class="col-12">
                <label class="form-label" for="name">Nome completo</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" autocomplete="name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="phone">WhatsApp</label>
                <input type="tel" id="phone" name="phone" data-mask="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="(11) 99999-9999" autocomplete="tel" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" data-mask="cpf" class="form-control @error('cpf') is-invalid @enderror" value="{{ old('cpf') }}" placeholder="000.000.000-00" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="email">E-mail <small class="text-muted">(opcional, para receber o comprovante)</small></label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="birthdate">Data de nascimento</label>
                <input type="date" id="birthdate" name="birthdate" class="form-control @error('birthdate') is-invalid @enderror" value="{{ old('birthdate') }}" max="{{ now()->subYears(config('saas.minimum_age'))->toDateString() }}" required>
              </div>
              <div class="col-12">
                <div class="form-check mb-2">
                  <input class="form-check-input @error('adult') is-invalid @enderror" type="checkbox" id="adult" name="adult" value="1" @checked(old('adult')) required>
                  <label class="form-check-label" for="adult">Declaro ter {{ config('saas.minimum_age') }} anos ou mais.</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input @error('accept_terms') is-invalid @enderror" type="checkbox" id="accept_terms" name="accept_terms" value="1" @checked(old('accept_terms')) required>
                  <label class="form-check-label" for="accept_terms">
                    Li e aceito o <a href="{{ route('public.raffle', [$tenant, $raffle]) }}#regulation" target="_blank">regulamento da rifa</a>,
                    os <a href="{{ route('public.terms') }}" target="_blank">Termos de Uso</a> e a <a href="{{ route('public.privacy') }}" target="_blank">Política de Privacidade</a>.
                  </label>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="col-lg-5 card-body p-md-8">
          <h4 class="mb-2">Resumo</h4>
          <p class="mb-6">{{ $raffle->title }}</p>
          <div class="bg-lighter p-6 rounded">
            <p class="mb-1">Números</p>
            @if ($numbers->isNotEmpty())
              <div class="d-flex flex-wrap gap-1 mb-4">
                @foreach ($numbers as $n)
                  <span class="badge bg-label-primary">{{ $raffle->formatNumber($n) }}</span>
                @endforeach
              </div>
            @else
              <p class="text-heading fw-medium mb-4"><i class="ti ti-arrows-shuffle me-1"></i>{{ $quantity }} número(s) aleatório(s)</p>
            @endif
            <div class="d-flex align-items-center">
              <h1 class="text-heading mb-0">{{ money_br($total) }}</h1>
            </div>
            <small class="text-muted">{{ $quantity }} × {{ $raffle->priceFormatted() }}</small>
          </div>
          <div class="mt-5">
            <div class="d-flex justify-content-between align-items-center">
              <p class="mb-0">Forma de pagamento</p>
              <h6 class="mb-0"><i class="ti ti-qrcode me-1"></i>Pix</h6>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <p class="mb-0">Reserva válida por</p>
              <h6 class="mb-0">{{ config('saas.reservation_ttl') }} minutos</h6>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mt-4 pb-1">
              <p class="mb-0">Total</p>
              <h6 class="mb-0">{{ money_br($total) }}</h6>
            </div>
            <div class="d-grid mt-5">
              <button type="submit" form="checkoutForm" class="btn btn-success">
                <span class="me-2">Gerar Pix</span>
                <i class="ti ti-arrow-right scaleX-n1-rtl"></i>
              </button>
            </div>
            <p class="mt-8 mb-0 small">
              Ao continuar, os números ficam reservados para você até o fim do prazo.
              <a href="{{ route('public.raffle', [$tenant, $raffle]) }}">Voltar e alterar a escolha</a>.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
