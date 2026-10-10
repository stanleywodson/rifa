{{-- Faixa de indicadores (base: card-widget-separator de app-ecommerce-order-list) --}}
{{-- $items = [['value' => ..., 'label' => ..., 'icon' => 'ti-...'], ...] --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        @foreach ($items as $i => $item)
          <div class="col-sm-6 col-lg-{{ 12 / max(count($items), 1) }}">
            <div class="d-flex justify-content-between align-items-start {{ $loop->last ? '' : 'border-end' }} pb-4 pb-sm-0 card-widget-{{ $i + 1 }}">
              <div>
                <h4 class="mb-0">{{ $item['value'] }}</h4>
                <p class="mb-0">{{ $item['label'] }}</p>
              </div>
              <span class="avatar me-sm-6">
                <span class="avatar-initial bg-label-{{ $item['color'] ?? 'secondary' }} rounded text-heading">
                  <i class="ti-26px ti {{ $item['icon'] }} text-heading"></i>
                </span>
              </span>
            </div>
            @unless ($loop->last)<hr class="d-none d-sm-block d-lg-none me-6">@endunless
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
