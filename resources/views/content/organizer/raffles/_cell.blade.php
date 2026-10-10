{{-- Célula de rifa (base: coluna "product" de app-ecommerce-product-list) --}}
@php $url = $url ?? route('painel.rifas.show', $raffle); $cover = $raffle->coverUrl(); @endphp
<div class="d-flex justify-content-start align-items-center product-name">
  <div class="avatar-wrapper">
    <div class="avatar avatar me-4 rounded-2 bg-label-secondary">
      @if ($cover)
        <img src="{{ $cover }}" alt="" class="rounded-2 object-fit-cover">
      @else
        <span class="avatar-initial rounded-2 bg-label-primary"><i class="ti ti-ticket"></i></span>
      @endif
    </div>
  </div>
  <div class="d-flex flex-column">
    <h6 class="text-nowrap mb-0"><a href="{{ $url }}" class="text-heading">{{ \Illuminate\Support\Str::limit($raffle->title, 40) }}</a></h6>
    <small class="text-truncate d-none d-sm-block">{{ number_format($raffle->total_numbers, 0, ',', '.') }} números · {{ $raffle->draw_method->label() }}</small>
  </div>
</div>
