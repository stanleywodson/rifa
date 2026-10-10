{{-- Card "Statistics" (base: app-ecommerce-dashboard) --}}
<div class="card h-100">
  <div class="card-header d-flex justify-content-between">
    <h5 class="card-title mb-0">{{ $title }}</h5>
    @isset($subtitle)<small class="text-muted">{{ $subtitle }}</small>@endisset
  </div>
  <div class="card-body d-flex align-items-end">
    <div class="w-100">
      <div class="row gy-3">
        @foreach ($items as $item)
          <div class="col-md-{{ 12 / max(count($items), 1) }} col-6">
            <div class="d-flex align-items-center">
              <div class="badge rounded bg-label-{{ $item['color'] }} me-4 p-2"><i class="ti {{ $item['icon'] }} ti-lg"></i></div>
              <div class="card-info">
                <h5 class="mb-0">{{ $item['value'] }}</h5>
                <small>{{ $item['label'] }}</small>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
