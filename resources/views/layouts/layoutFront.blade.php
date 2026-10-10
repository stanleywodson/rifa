@php
$configData = Helper::appClasses();
$isFront = true;
@endphp

@section('layoutContent')

@extends('layouts/commonMaster' )

@include(request()->is('demo/*') ? 'layouts/sections/navbar/navbar-front' : 'layouts/sections/navbar/navbar-front-saas')

<!-- Sections:Start -->
@if (!request()->is('demo/*') && collect(['success', 'error', 'warning', 'info'])->contains(fn($k) => session()->has($k)))
  <div class="position-fixed start-50 translate-middle-x w-100 px-4" style="top: 6rem; z-index: 1090; max-width: 760px">@include('_partials.saas.flash', ['class' => 'mb-2 shadow', 'hideErrors' => true])</div>
@endif
@yield('content')
<!-- / Sections:End -->

@include(request()->is('demo/*') ? 'layouts/sections/footer/footer-front' : 'layouts/sections/footer/footer-front-saas')
@endsection
