{{-- Navbar das páginas públicas (base: navbar-front.blade.php do template) --}}
@php
$brandUrl = isset($tenant) && $tenant ? route('public.tenant', $tenant) : route('home');
@endphp
<!-- Navbar: Start -->
<nav class="layout-navbar shadow-none py-0">
  <div class="container">
    <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-8">
      <!-- Menu logo wrapper: Start -->
      <div class="navbar-brand app-brand demo d-flex py-0 py-lg-2 me-4 me-xl-8">
        <button class="navbar-toggler border-0 px-0 me-4" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Abrir menu">
          <i class="ti ti-menu-2 ti-lg align-middle text-heading fw-medium"></i>
        </button>
        <a href="{{ $brandUrl }}" class="app-brand-link">
          @if (isset($tenant) && $tenant)
            <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="ti ti-building-store"></i></span></span>
            <span class="app-brand-text demo menu-text fw-bold ms-2 ps-1">{{ $tenant->name }}</span>
          @else
            <span class="app-brand-logo demo">@include('_partials.macros',['height'=>20,'withbg' => "fill: #fff;"])</span>
            <span class="app-brand-text demo menu-text fw-bold ms-2 ps-1">{{ config('variables.templateName') }}</span>
          @endif
        </a>
      </div>
      <!-- Menu logo wrapper: End -->
      <!-- Menu wrapper: Start -->
      <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
        <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-label="Fechar menu">
          <i class="ti ti-x ti-lg"></i>
        </button>
        <ul class="navbar-nav me-auto">
          @if (isset($tenant) && $tenant)
            <li class="nav-item"><a class="nav-link fw-medium" href="{{ route('public.tenant', $tenant) }}">Rifas</a></li>
          @else
            <li class="nav-item"><a class="nav-link fw-medium" href="{{ route('home') }}#landingHero">Início</a></li>
            <li class="nav-item"><a class="nav-link fw-medium" href="{{ route('home') }}#landingFeatures">Como funciona</a></li>
            <li class="nav-item"><a class="nav-link fw-medium" href="{{ route('home') }}#landingPricing">Planos</a></li>
            <li class="nav-item"><a class="nav-link fw-medium" href="{{ route('home') }}#landingFAQ">Dúvidas</a></li>
          @endif
          <li class="nav-item"><a class="nav-link fw-medium {{ request()->routeIs('public.my-tickets*') ? 'active' : '' }}" href="{{ route('public.my-tickets') }}">Meus números</a></li>
        </ul>
      </div>
      <div class="landing-menu-overlay d-lg-none"></div>
      <!-- Menu wrapper: End -->
      <!-- Toolbar: Start -->
      <ul class="navbar-nav flex-row align-items-center ms-auto">
        @if($configData['hasCustomizer'] == true)
        <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-1">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <i class='ti ti-lg'></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
            <li><a class="dropdown-item" href="javascript:void(0);" data-theme="light"><span class="align-middle"><i class='ti ti-sun me-3'></i>Claro</span></a></li>
            <li><a class="dropdown-item" href="javascript:void(0);" data-theme="dark"><span class="align-middle"><i class="ti ti-moon-stars me-3"></i>Escuro</span></a></li>
            <li><a class="dropdown-item" href="javascript:void(0);" data-theme="system"><span class="align-middle"><i class="ti ti-device-desktop-analytics me-3"></i>Sistema</span></a></li>
          </ul>
        </li>
        @endif
        @unless (isset($tenant) && $tenant)
        <li>
          @auth
            <a href="{{ route('painel.dashboard') }}" class="btn btn-primary"><span class="tf-icons ti ti-layout-dashboard scaleX-n1-rtl me-md-1"></span><span class="d-none d-md-block">Painel</span></a>
          @else
            <a href="{{ route('login') }}" class="btn btn-primary"><span class="tf-icons ti ti-login scaleX-n1-rtl me-md-1"></span><span class="d-none d-md-block">Entrar / Criar conta</span></a>
          @endauth
        </li>
        @endunless
      </ul>
      <!-- Toolbar: End -->
    </div>
  </div>
</nav>
<!-- Navbar: End -->
