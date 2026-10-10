{{-- Layout das telas de autenticação, baseado em content/authentications/auth-*-cover --}}
@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$illustration = $illustration ?? 'auth-login-illustration';
@endphp

@extends('layouts/layoutMaster')

@section('page-style')
@vite([
  'resources/assets/vendor/scss/pages/page-auth.scss'
])
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
  <a href="{{ route('home') }}" class="app-brand auth-cover-brand">
    <span class="app-brand-logo demo">@include('_partials.macros',['height'=>20,'withbg' => "fill: #fff;"])</span>
    <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row m-0">
    <!-- Left Text -->
    <div class="d-none d-lg-flex col-lg-8 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/'.$illustration.'-'.$configData['style'].'.png') }}" alt="" class="my-5 auth-illustration" data-app-light-img="illustrations/{{ $illustration }}-light.png" data-app-dark-img="illustrations/{{ $illustration }}-dark.png">
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-'.$configData['style'].'.png') }}" alt="" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png">
      </div>
    </div>
    <!-- /Left Text -->

    <div class="d-flex col-12 col-lg-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-12 pt-5">
        @if (session('status'))
          <div class="alert alert-success" role="alert">{{ session('status') }}</div>
        @endif
        @yield('auth-form')
      </div>
    </div>
  </div>
</div>
@endsection
