{{-- Card de rifa (base: cards-basic do template) --}}
@php
$sold = $raffle->soldCount();
$percent = $raffle->total_numbers ? round($sold * 100 / $raffle->total_numbers) : 0;
@endphp
<div class="card h-100">
  <a href="{{ route('public.raffle', [$raffle->tenant->slug, $raffle->slug]) }}">
    @if ($cover = $raffle->coverUrl())
      <img class="card-img-top object-fit-cover" src="{{ $cover }}" alt="{{ $raffle->title }}" style="height: 200px">
    @else
      <div class="card-img-top bg-label-primary d-flex align-items-center justify-content-center" style="height: 200px"><i class="ti ti-gift ti-xl"></i></div>
    @endif
  </a>
  <div class="card-body d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-2">
      {!! $raffle->status->badge() !!}
      <span class="fw-medium text-heading">{{ $raffle->priceFormatted() }} <small class="text-muted fw-normal">/ número</small></span>
    </div>
    <h5 class="card-title mb-1">
      <a href="{{ route('public.raffle', [$raffle->tenant->slug, $raffle->slug]) }}" class="text-heading">{{ $raffle->title }}</a>
    </h5>
    @if ($showTenant ?? false)
      <small class="text-muted mb-2"><i class="ti ti-building-store ti-xs me-1"></i>{{ $raffle->tenant->name }}</small>
    @endif
    <p class="card-text small mb-4">{{ \Illuminate\Support\Str::limit($raffle->summary ?: $raffle->prize?->title, 110) }}</p>
    <div class="mt-auto">
      <div class="d-flex justify-content-between small mb-1">
        <span>{{ $percent }}% vendido</span>
        @if ($raffle->draw_at)<span><i class="ti ti-calendar ti-xs me-1"></i>{{ $raffle->draw_at->format('d/m/Y') }}</span>@endif
      </div>
      <div class="progress mb-4" style="height: 8px">
        <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
      <a href="{{ route('public.raffle', [$raffle->tenant->slug, $raffle->slug]) }}" class="btn btn-primary w-100">
        {{ $raffle->isOnSale() ? 'Escolher números' : 'Ver rifa' }}
      </a>
    </div>
  </div>
</div>
