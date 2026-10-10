{{-- Mensagens de retorno (alerts do template: ui-alerts) --}}
@foreach (['success' => ['success', 'ti-check'], 'error' => ['danger', 'ti-ban'], 'warning' => ['warning', 'ti-alert-triangle'], 'info' => ['info', 'ti-info-circle']] as $key => [$color, $icon])
  @if (session($key))
    <div class="alert alert-{{ $color }} alert-dismissible d-flex align-items-center {{ $class ?? 'mb-6' }}" role="alert">
      <span class="alert-icon rounded"><i class="ti {{ $icon }}"></i></span>
      <span>{{ session($key) }}</span>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  @endif
@endforeach
@if ($errors->any() && !($hideErrors ?? false))
  <div class="alert alert-danger {{ $class ?? 'mb-6' }}" role="alert">
    <h6 class="alert-heading mb-1"><i class="ti ti-alert-circle me-1"></i>Verifique os campos abaixo</h6>
    <ul class="mb-0 ps-4">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
