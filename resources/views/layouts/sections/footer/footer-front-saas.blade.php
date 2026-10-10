{{-- Rodapé das páginas públicas (base: footer-front.blade.php do template) --}}
<!-- Footer: Start -->
<footer class="landing-footer bg-body footer-text">
  <div class="footer-top position-relative overflow-hidden z-1">
    <img src="{{asset('assets/img/front-pages/backgrounds/footer-bg-'.$configData['style'].'.png')}}" alt="" class="footer-bg banner-bg-img z-n1" data-app-light-img="front-pages/backgrounds/footer-bg-light.png" data-app-dark-img="front-pages/backgrounds/footer-bg-dark.png" />
    <div class="container">
      <div class="row gx-0 gy-6 g-lg-10">
        <div class="col-lg-5">
          <a href="{{ route('home') }}" class="app-brand-link mb-6">
            <span class="app-brand-logo demo">@include('_partials.macros',['height'=>20,'withbg' => "fill: #fff;"])</span>
            <span class="app-brand-text demo footer-link fw-bold ms-2 ps-1">{{ config('variables.templateName') }}</span>
          </a>
          <p class="footer-text footer-logo-description mb-6">
            Plataforma para rifas autorizadas com pagamento via Pix e sorteio auditável.
            Cada organizador é responsável pela autorização e pela entrega do prêmio.
          </p>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <h6 class="footer-title mb-6">Participantes</h6>
          <ul class="list-unstyled">
            <li class="mb-4"><a href="{{ route('public.my-tickets') }}" class="footer-link">Consultar meus números</a></li>
            <li class="mb-4"><a href="{{ route('home') }}#landingFAQ" class="footer-link">Como funciona o sorteio</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <h6 class="footer-title mb-6">Organizadores</h6>
          <ul class="list-unstyled">
            <li class="mb-4"><a href="{{ route('register') }}" class="footer-link">Criar conta</a></li>
            <li class="mb-4"><a href="{{ route('login') }}" class="footer-link">Entrar</a></li>
            <li class="mb-4"><a href="{{ route('home') }}#landingPricing" class="footer-link">Planos</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-4">
          <h6 class="footer-title mb-6">Legal</h6>
          <ul class="list-unstyled">
            <li class="mb-4"><a href="{{ route('public.terms') }}" class="footer-link">Termos de Uso</a></li>
            <li class="mb-4"><a href="{{ route('public.privacy') }}" class="footer-link">Privacidade (LGPD)</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom py-3 py-md-5">
    <div class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
      <div class="mb-2 mb-md-0">
        <span class="footer-bottom-text">© {{ date('Y') }} {{ config('variables.templateName') }}. Proibida a participação de menores de 18 anos.</span>
      </div>
    </div>
  </div>
</footer>
<!-- Footer: End -->
