@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', $tenant->name)

@section('page-meta')
@include('content.public._meta', ['title' => $tenant->name, 'description' => $tenant->settings['about'] ?? 'Rifas de ' . $tenant->name, 'image' => $raffles->first()?->coverUrl()])
@endsection

@section('content')
<section class="section-py bg-body first-section-pt">
  <div class="container">
    <div class="card mb-6">
      <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <div class="avatar avatar-xl"><span class="avatar-initial rounded bg-label-primary"><i class="ti ti-building-store ti-lg"></i></span></div>
        <div class="flex-grow-1">
          <h4 class="mb-1">{{ $tenant->name }}
            @if ($tenant->isVerified())<span class="badge bg-label-success align-middle ms-1"><i class="ti ti-rosette-discount-check ti-xs me-1"></i>Verificado</span>@endif
          </h4>
          <p class="mb-0">{{ $tenant->settings['about'] ?? '' }}</p>
        </div>
        @if (!empty($tenant->settings['instagram']))
          <a href="https://instagram.com/{{ ltrim($tenant->settings['instagram'], '@') }}" target="_blank" rel="noopener" class="btn btn-label-primary"><i class="ti ti-brand-instagram me-1"></i>{{ '@' . ltrim($tenant->settings['instagram'], '@') }}</a>
        @endif
      </div>
    </div>
    <div class="row g-6">
      @forelse ($raffles as $raffle)
        <div class="col-xl-4 col-md-6">@include('content.public._raffle-card', ['raffle' => $raffle])</div>
      @empty
        <div class="col-12"><div class="alert alert-secondary">Nenhuma rifa publicada no momento.</div></div>
      @endforelse
    </div>
  </div>
</section>
@endsection
