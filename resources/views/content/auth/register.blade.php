@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$stepErrors = [
  0 => $errors->hasAny(['name', 'email', 'phone', 'password']),
  1 => $errors->hasAny(['organization', 'slug', 'document']),
  2 => $errors->hasAny(['terms', 'adult']),
];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Criar conta de organizador')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/cleavejs/cleave.js',
  'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js'])
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover authentication-bg">
  <!-- Logo -->
  <a href="{{ route('home') }}" class="app-brand auth-cover-brand">
    <span class="app-brand-logo demo">@include('_partials.macros',['height'=>20,'withbg' => "fill: #fff;"])</span>
    <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row">

    <!-- Left Text -->
    <div class="d-none d-lg-flex col-lg-4 align-items-center justify-content-center p-5 auth-cover-bg-color position-relative auth-multisteps-bg-height">
      <img src="{{ asset('assets/img/illustrations/auth-register-multisteps-illustration.png') }}" alt="" class="img-fluid" width="280">
      <img src="{{ asset('assets/img/illustrations/auth-register-multisteps-shape-'.$configData['style'].'.png') }}" alt="" class="platform-bg" data-app-light-img="illustrations/auth-register-multisteps-shape-light.png" data-app-dark-img="illustrations/auth-register-multisteps-shape-dark.png">
    </div>
    <!-- /Left Text -->

    <div class="d-flex col-lg-8 align-items-center justify-content-center authentication-bg p-5">
      <div class="w-px-700">
        <div class="bs-stepper saas-stepper border-none shadow-none mt-5" data-error-step="{{ collect($stepErrors)->filter()->keys()->first() ?? 0 }}">
          <div class="bs-stepper-header border-none pt-12 px-0">
            <div class="step" data-target="#stepAccount">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle"><i class="ti ti-user ti-md"></i></span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Conta</span>
                  <span class="bs-stepper-subtitle">Seus dados de acesso</span>
                </span>
              </button>
            </div>
            <div class="line"><i class="ti ti-chevron-right"></i></div>
            <div class="step" data-target="#stepOrganization">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle"><i class="ti ti-building-store ti-md"></i></span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Organização</span>
                  <span class="bs-stepper-subtitle">Quem promove as rifas</span>
                </span>
              </button>
            </div>
            <div class="line"><i class="ti ti-chevron-right"></i></div>
            <div class="step" data-target="#stepTerms">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle"><i class="ti ti-file-certificate ti-md"></i></span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Termos</span>
                  <span class="bs-stepper-subtitle">Responsabilidades</span>
                </span>
              </button>
            </div>
          </div>
          <div class="bs-stepper-content px-0">
            <form action="{{ route('register') }}" method="POST">
              @csrf
              <!-- Conta -->
              <div id="stepAccount" class="content">
                <div class="content-header mb-6">
                  <h4 class="mb-0">Dados de acesso</h4>
                  <p class="mb-0">Você será o responsável (proprietário) da conta</p>
                </div>
                <div class="row g-6">
                  <div class="col-sm-6">
                    <label class="form-label" for="name">Nome completo</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required />
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required />
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="phone">WhatsApp</label>
                    <div class="input-group">
                      <span class="input-group-text">BR (+55)</span>
                      <input type="text" name="phone" id="phone" value="{{ old('phone') }}" data-mask="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="(11) 99999-9999" required />
                    </div>
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-sm-6"></div>
                  <div class="col-sm-6 form-password-toggle">
                    <label class="form-label" for="password">Senha</label>
                    <div class="input-group input-group-merge">
                      <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required />
                      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                    </div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-sm-6 form-password-toggle">
                    <label class="form-label" for="password_confirmation">Confirme a senha</label>
                    <div class="input-group input-group-merge">
                      <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required />
                      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                    </div>
                  </div>
                  <div class="col-12 d-flex justify-content-between">
                    <button type="button" class="btn btn-label-secondary btn-prev" disabled><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
                    <button type="button" class="btn btn-primary btn-next"><span class="align-middle d-sm-inline-block d-none me-sm-1 me-0">Próximo</span> <i class="ti ti-arrow-right ti-xs"></i></button>
                  </div>
                </div>
              </div>

              <!-- Organização -->
              <div id="stepOrganization" class="content">
                <div class="content-header mb-6">
                  <h4 class="mb-0">Organização</h4>
                  <p class="mb-0">Pessoa física, associação, ONG, igreja ou empresa</p>
                </div>
                <div class="row g-6">
                  <div class="col-sm-6">
                    <label class="form-label" for="organization">Nome da organização</label>
                    <input type="text" name="organization" id="organization" value="{{ old('organization') }}" data-slug-source="#slug" class="form-control @error('organization') is-invalid @enderror" required />
                    @error('organization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="document">CPF ou CNPJ</label>
                    <input type="text" name="document" id="document" value="{{ old('document') }}" data-mask="document" class="form-control @error('document') is-invalid @enderror" required />
                    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="slug">Endereço da sua página</label>
                    <div class="input-group">
                      <span class="input-group-text">{{ url('/') }}/</span>
                      <input type="text" name="slug" id="slug" value="{{ old('slug') }}" class="form-control @error('slug') is-invalid @enderror" placeholder="minha-organizacao" required />
                    </div>
                    @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-12 d-flex justify-content-between">
                    <button type="button" class="btn btn-label-secondary btn-prev"><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
                    <button type="button" class="btn btn-primary btn-next"><span class="align-middle d-sm-inline-block d-none me-sm-1 me-0">Próximo</span> <i class="ti ti-arrow-right ti-xs"></i></button>
                  </div>
                </div>
              </div>

              <!-- Termos -->
              <div id="stepTerms" class="content">
                <div class="content-header mb-6">
                  <h4 class="mb-0">Termos e responsabilidades</h4>
                  <p class="mb-0">Leia com atenção antes de continuar</p>
                </div>
                <div class="alert alert-warning d-flex align-items-start" role="alert">
                  <span class="alert-icon rounded"><i class="ti ti-alert-triangle"></i></span>
                  <span>Rifas e sorteios com distribuição de prêmios dependem de <strong>autorização prévia</strong> do órgão competente
                    (Lei 5.768/1971). Cada rifa só é publicada após análise da plataforma e envio do documento de autorização.</span>
                </div>
                <div class="row g-4">
                  <div class="col-12">
                    <div class="form-check">
                      <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" @checked(old('terms'))>
                      <label class="form-check-label" for="terms">
                        Li e aceito os <a href="{{ route('public.terms') }}" target="_blank">Termos de Uso</a> e a
                        <a href="{{ route('public.privacy') }}" target="_blank">Política de Privacidade</a>, e assumo a responsabilidade legal pelas rifas da organização.
                      </label>
                      @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-check">
                      <input class="form-check-input @error('adult') is-invalid @enderror" type="checkbox" id="adult" name="adult" value="1" @checked(old('adult'))>
                      <label class="form-check-label" for="adult">Declaro ter 18 anos ou mais.</label>
                      @error('adult')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12 d-flex justify-content-between">
                    <button type="button" class="btn btn-label-secondary btn-prev"><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
                    <button type="submit" class="btn btn-success">Criar conta</button>
                  </div>
                </div>
              </div>
            </form>
            <p class="text-center mt-6 mb-0">
              <span>Já tem conta?</span>
              <a href="{{ route('login') }}"><span>Entrar</span></a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
