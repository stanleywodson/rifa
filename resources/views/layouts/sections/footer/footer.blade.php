@php
$containerFooter = (isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      @if (request()->is('demo/*'))
        <div class="text-body">
          © {{ date('Y') }}, made with ❤️ by <a href="{{ config('variables.creatorUrl') }}" target="_blank" class="footer-link">{{ config('variables.creatorName') }}</a>
        </div>
      @else
        <div class="text-body">© {{ date('Y') }} {{ config('variables.templateName') }}</div>
        <div class="d-none d-lg-inline-block">
          <a href="{{ route('public.terms') }}" class="footer-link me-4" target="_blank">Termos de Uso</a>
          <a href="{{ route('public.privacy') }}" class="footer-link me-4" target="_blank">Privacidade</a>
          <a href="{{ route('home') }}" class="footer-link" target="_blank">Site</a>
        </div>
      @endif
    </div>
  </div>
</footer>
<!--/ Footer-->
