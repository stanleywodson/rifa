@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
$containerNav = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
  @endif
  @if(isset($navbarDetached) && $navbarDetached == '')
  <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="{{$containerNav}}">
      @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
        <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
          <a href="{{url('/')}}" class="app-brand-link">
            <span class="app-brand-logo demo">@include('_partials.macros',["height"=>20])</span>
            <span class="app-brand-text demo menu-text fw-bold">{{config('variables.templateName')}}</span>
          </a>
          @if(isset($menuHorizontal))
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
              <i class="ti ti-x ti-md align-middle"></i>
            </a>
          @endif
        </div>
      @endif

      <!-- ! Not required for layout-without-menu -->
      @if(!isset($navbarHideToggle))
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
          <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="ti ti-menu-2 ti-md"></i>
          </a>
        </div>
      @endif

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        @isset($currentTenant)
        <!-- Organizador ativo -->
        <div class="navbar-nav align-items-center">
          <div class="nav-item d-flex align-items-center">
            <i class="ti ti-building-store ti-md me-2"></i>
            <span class="fw-medium text-heading d-none d-md-inline-block">{{ $currentTenant->name }}</span>
            <span class="ms-2">{!! $currentTenant->kyc_status->badge() !!}</span>
          </div>
        </div>
        <!-- /Organizador ativo -->
        @endisset

        <ul class="navbar-nav flex-row align-items-center ms-auto">

          @if($configData['hasCustomizer'] == true)
            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown">
              <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class='ti ti-md'></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                <li>
                  <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                    <span class="align-middle"><i class='ti ti-sun ti-md me-3'></i>Claro</span>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                    <span class="align-middle"><i class="ti ti-moon-stars ti-md me-3"></i>Escuro</span>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                    <span class="align-middle"><i class="ti ti-device-desktop-analytics ti-md me-3"></i>Sistema</span>
                  </a>
                </li>
              </ul>
            </li>
            <!-- / Style Switcher -->
          @endif

          @isset($currentTenant)
          <!-- Página pública -->
          <li class="nav-item me-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill" href="{{ route('public.tenant', $currentTenant) }}" target="_blank" title="Ver página pública">
              <i class="ti ti-external-link ti-md"></i>
            </a>
          </li>
          @endisset

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                <span class="avatar-initial rounded-circle bg-label-primary">{{ Auth::user()?->initials() }}</span>
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item mt-0" href="{{ route('conta.edit') }}">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-2">
                      <div class="avatar avatar-online">
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ Auth::user()?->initials() }}</span>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0">{{ Auth::user()?->name }}</h6>
                      <small class="text-muted">
                        @if (Auth::user()?->isPlatformAdmin())
                          Administrador da plataforma
                        @elseif (isset($currentTenant))
                          {{ Auth::user()->roleIn($currentTenant)?->label() }}
                        @endif
                      </small>
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider my-1 mx-n2"></div>
              </li>
              <li>
                <a class="dropdown-item" href="{{ route('conta.edit') }}">
                  <i class="ti ti-user me-3 ti-md"></i><span class="align-middle">Minha conta</span>
                </a>
              </li>
              @if (Auth::user()?->tenants->isNotEmpty())
                <li>
                  <a class="dropdown-item" href="{{ route('painel.dashboard') }}">
                    <i class="ti ti-layout-dashboard me-3 ti-md"></i><span class="align-middle">Painel do organizador</span>
                  </a>
                </li>
              @endif
              @if (Auth::user()?->isPlatformAdmin())
                <li>
                  <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                    <i class="ti ti-shield-lock me-3 ti-md"></i><span class="align-middle">Administração</span>
                  </a>
                </li>
              @endif
              @if (Auth::user()?->tenants->count() > 1)
                <li>
                  <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                  <h6 class="dropdown-header">Trocar organizador</h6>
                </li>
                @foreach (Auth::user()->tenants as $tenantOption)
                  <li>
                    <form method="POST" action="{{ route('painel.switch', $tenantOption) }}">
                      @csrf
                      <button type="submit" class="dropdown-item {{ isset($currentTenant) && $currentTenant->id === $tenantOption->id ? 'active' : '' }}">
                        <i class="ti ti-building-store me-3 ti-md"></i><span class="align-middle">{{ $tenantOption->name }}</span>
                      </button>
                    </form>
                  </li>
                @endforeach
              @endif
              <li>
                <div class="dropdown-divider my-1 mx-n2"></div>
              </li>
              @auth
                <li>
                  <div class="d-grid px-2 pt-2 pb-1">
                    <a class="btn btn-sm btn-danger d-flex" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                      <small class="align-middle">Sair</small>
                      <i class="ti ti-logout ms-2 ti-14px"></i>
                    </a>
                  </div>
                </li>
                <form method="POST" id="logout-form" action="{{ route('logout') }}">
                  @csrf
                </form>
              @else
                <li>
                  <div class="d-grid px-2 pt-2 pb-1">
                    <a class="btn btn-sm btn-primary d-flex" href="{{ route('login') }}">
                      <small class="align-middle">Entrar</small>
                      <i class="ti ti-login ms-2 ti-14px"></i>
                    </a>
                  </div>
                </li>
              @endauth
            </ul>
          </li>
          <!--/ User -->
        </ul>
      </div>

      @if(isset($navbarDetached) && $navbarDetached == '')
    </div>
    @endif
  </nav>
  <!-- / Navbar -->
