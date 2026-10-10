@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Rifas online com Pix e sorteio auditável')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/front-page-landing.scss'])
@endsection

@section('content')
<div data-bs-spy="scroll" class="scrollspy-example">
  <!-- Hero: Start -->
  <section id="hero-animation">
    <div id="landingHero" class="section-py landing-hero position-relative">
      <img src="{{ asset('assets/img/front-pages/backgrounds/hero-bg.png') }}" alt="" class="position-absolute top-0 start-50 translate-middle-x object-fit-cover w-100 h-100" />
      <div class="container">
        <div class="hero-text-box text-center position-relative">
          <h1 class="text-primary hero-title display-6 fw-extrabold">Rifas online, do Pix ao sorteio, com total transparência</h1>
          <h2 class="hero-sub-title h6 mb-6">
            Crie sua rifa em minutos, receba via Pix com repasse automático<br class="d-none d-lg-block" />
            e realize o sorteio com prova pública verificável.
          </h2>
          <div class="landing-hero-btn d-inline-block position-relative">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Criar minha rifa</a>
            <a href="{{ route('public.my-tickets') }}" class="btn btn-label-primary btn-lg ms-2">Consultar meus números</a>
          </div>
        </div>
      </div>
    </div>
    <div class="landing-hero-blank"></div>
  </section>
  <!-- Hero: End -->

  @if ($raffles->isNotEmpty())
  <!-- Rifas em destaque: Start -->
  <section class="section-py bg-body">
    <div class="container">
      <div class="text-center mb-4"><span class="badge bg-label-primary">Em andamento</span></div>
      <h4 class="text-center mb-12">
        <span class="position-relative fw-extrabold z-1">Rifas abertas
          <img src="{{ asset('assets/img/front-pages/icons/section-title-icon.png') }}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1">
        </span>
        agora
      </h4>
      <div class="row g-6">
        @foreach ($raffles as $raffle)
          <div class="col-xl-4 col-md-6">@include('content.public._raffle-card', ['raffle' => $raffle, 'showTenant' => true])</div>
        @endforeach
      </div>
    </div>
  </section>
  <!-- Rifas em destaque: End -->
  @endif

  <!-- Features: Start -->
  <section id="landingFeatures" class="section-py landing-features">
    <div class="container">
      <div class="text-center mb-4"><span class="badge bg-label-primary">Como funciona</span></div>
      <h4 class="text-center mb-1">
        <span class="position-relative fw-extrabold z-1">Tudo o que você precisa
          <img src="{{ asset('assets/img/front-pages/icons/section-title-icon.png') }}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1">
        </span>
        para uma rifa segura
      </h4>
      <p class="text-center mb-12">Do cadastro à entrega do prêmio, com conformidade e prestação de contas.</p>
      <div class="features-icon-wrapper row gx-0 gy-6 g-sm-12">
        @foreach ([
          ['laptop.png', 'Crie em minutos', 'Título, prêmio, fotos, quantidade de números, preço e regulamento em um assistente passo a passo.'],
          ['check.png', 'Aprovação e compliance', 'Cada rifa é analisada pela plataforma com o documento de autorização antes de ser publicada.'],
          ['rocket.png', 'Pix automático', 'O comprador paga por Pix e os números são confirmados na hora. O valor vai direto para sua conta.'],
          ['paper.png', 'Sorteio auditável', 'Resultado baseado na Loteria Federal ou em semente com commit público, com ata em PDF.'],
          ['user.png', 'Página própria', 'Uma página pública para a sua organização e para cada rifa, pronta para compartilhar no WhatsApp.'],
          ['keyboard.png', 'Relatórios', 'Acompanhe vendas, compradores e exporte relatórios para prestação de contas.'],
        ] as [$icon, $title, $text])
          <div class="col-lg-4 col-sm-6 text-center features-icon-box">
            <div class="text-center mb-4"><img src="{{ asset('assets/img/front-pages/icons/'.$icon) }}" alt="" /></div>
            <h5 class="mb-2">{{ $title }}</h5>
            <p class="features-icon-description">{{ $text }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>
  <!-- Features: End -->

  <!-- Pricing: Start -->
  <section id="landingPricing" class="section-py bg-body landing-pricing">
    <div class="container">
      <div class="text-center mb-4"><span class="badge bg-label-primary">Planos</span></div>
      <h4 class="text-center mb-1">
        <span class="position-relative fw-extrabold z-1">Planos sob medida
          <img src="{{ asset('assets/img/front-pages/icons/section-title-icon.png') }}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1">
        </span>
        para cada organizador
      </h4>
      <p class="text-center pb-2 mb-12">Assinatura mensal + taxa por venda aprovada. Sem taxa sobre vendas não pagas.</p>
      <div class="row g-6 pt-lg-5">
        @foreach ($plans as $plan)
          @php $featured = $plan->code === 'pro'; @endphp
          <div class="col-xl-4 col-lg-6">
            <div class="card {{ $featured ? 'border border-primary shadow-xl' : '' }}">
              <div class="card-header">
                <div class="text-center">
                  <img src="{{ asset('assets/img/front-pages/icons/'.['starter' => 'paper-airplane.png', 'pro' => 'plane.png', 'business' => 'shuttle-rocket.png'][$plan->code] ?? 'paper-airplane.png') }}" alt="" class="mb-8 pb-2" />
                  <h4 class="mb-0">{{ $plan->name }}</h4>
                  <div class="d-flex align-items-center justify-content-center">
                    <span class="h2 text-primary fw-extrabold mb-0">{{ $plan->price_cents ? money_br($plan->price_cents) : 'Grátis' }}</span>
                    @if ($plan->price_cents)<sub class="h6 text-muted mb-n1 ms-1">/mês</sub>@endif
                  </div>
                </div>
              </div>
              <div class="card-body">
                <ul class="list-unstyled pricing-list">
                  @foreach ([
                    ($plan->limit('active_raffles') ? $plan->limit('active_raffles') . ' rifa(s) ativa(s)' : 'Rifas ativas ilimitadas'),
                    'Até ' . number_format($plan->limit('max_numbers', config('saas.max_numbers')), 0, ',', '.') . ' números por rifa',
                    'Taxa de ' . rtrim(rtrim(number_format($plan->fee_percent, 2, ',', ''), '0'), ',') . '% por venda',
                    'Pix com repasse automático',
                    'Sorteio auditável e ata em PDF',
                  ] as $feature)
                    <li>
                      <h6 class="mb-3">
                        <span class="badge badge-center rounded-pill bg-label-primary p-0 me-2"><i class="ti ti-check ti-14px"></i></span>
                        {{ $feature }}
                      </h6>
                    </li>
                  @endforeach
                </ul>
                <div class="d-grid mt-8">
                  <a href="{{ route('register') }}" class="btn {{ $featured ? 'btn-primary' : 'btn-label-primary' }}">Começar</a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
  <!-- Pricing: End -->

  <!-- FAQ: Start -->
  <section id="landingFAQ" class="section-py landing-faq">
    <div class="container">
      <div class="text-center mb-4"><span class="badge bg-label-primary">Dúvidas</span></div>
      <h4 class="text-center mb-1">Perguntas
        <span class="position-relative fw-extrabold z-1">frequentes
          <img src="{{ asset('assets/img/front-pages/icons/section-title-icon.png') }}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1">
        </span>
      </h4>
      <p class="text-center mb-12 pb-md-4">Transparência para quem organiza e para quem participa.</p>
      <div class="row gy-12 align-items-center">
        <div class="col-lg-5">
          <div class="text-center">
            <img src="{{ asset('assets/img/front-pages/landing-page/faq-boy-with-logos.png') }}" alt="" class="faq-image" />
          </div>
        </div>
        <div class="col-lg-7">
          <div class="accordion" id="accordionFaq">
            @foreach ([
              ['Como o número vencedor é escolhido?', 'Cada rifa define, antes do início das vendas, o método de sorteio: o 1º prêmio da Loteria Federal (resultado público), uma semente secreta cujo hash é publicado na aprovação (commit-reveal) combinada com a Loteria Federal, ou sorteio ao vivo gravado. Depois do sorteio, a prova com todos os dados para recalcular o resultado fica pública.'],
              ['E se o número sorteado não tiver sido vendido?', 'A regra é definida no regulamento e não pode ser alterada após a publicação: próximo número pago acima, novo sorteio no próximo concurso, ou anulação com reembolso integral.'],
              ['Quanto tempo tenho para pagar o Pix?', 'Os números ficam reservados por ' . config('saas.reservation_ttl') . ' minutos. Depois disso a reserva expira e os números voltam a ficar disponíveis.'],
              ['A rifa é legal?', 'Sorteios com distribuição de prêmios dependem de autorização prévia (Lei 5.768/1971). A plataforma exige o número e o documento de autorização de cada rifa e faz análise manual antes da publicação. A responsabilidade pela rifa e pela entrega do prêmio é do organizador.'],
              ['Menores de idade podem participar?', 'Não. A participação é permitida somente para maiores de 18 anos.'],
            ] as $i => [$q, $a])
              <div class="card accordion-item {{ $i === 0 ? 'active' : '' }}">
                <h2 class="accordion-header">
                  <button type="button" class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">{{ $q }}</button>
                </h2>
                <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#accordionFaq">
                  <div class="accordion-body">{{ $a }}</div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- FAQ: End -->

  <!-- CTA: Start -->
  <section id="landingCTA" class="section-py landing-cta position-relative p-lg-0 pb-0">
    <img src="{{ asset('assets/img/front-pages/backgrounds/cta-bg-'.$configData['style'].'.png') }}" class="position-absolute bottom-0 end-0 scaleX-n1-rtl h-100 w-100 z-n1" alt="" data-app-light-img="front-pages/backgrounds/cta-bg-light.png" data-app-dark-img="front-pages/backgrounds/cta-bg-dark.png" />
    <div class="container">
      <div class="row align-items-center gy-12">
        <div class="col-lg-6 text-start text-sm-center text-lg-start">
          <h3 class="cta-title text-primary fw-bold mb-0">Pronto para começar?</h3>
          <h5 class="text-body mb-8">Crie sua conta de organizador gratuitamente</h5>
          <a href="{{ route('register') }}" class="btn btn-lg btn-primary">Criar conta</a>
        </div>
        <div class="col-lg-6 pt-lg-12 text-center text-lg-end">
          <img src="{{ asset('assets/img/front-pages/landing-page/cta-dashboard.png') }}" alt="" class="img-fluid mt-lg-4" />
        </div>
      </div>
    </div>
  </section>
  <!-- CTA: End -->
</div>
@endsection
