@extends('layouts/authCover', ['illustration' => 'auth-login-illustration'])

@section('title', 'Entrar')

@section('auth-form')
<h4 class="mb-1">Bem-vindo(a) ao {{ config('variables.templateName') }}! 👋</h4>
<p class="mb-6">Acesse sua conta para gerenciar suas rifas</p>

<form class="mb-6" action="{{ route('login') }}" method="POST">
  @csrf
  <div class="mb-6">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com" autofocus required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="mb-6 form-password-toggle">
    <label class="form-label" for="password">Senha</label>
    <div class="input-group input-group-merge">
      <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="my-8">
    <div class="d-flex justify-content-between">
      <div class="form-check mb-0 ms-2">
        <input class="form-check-input" type="checkbox" id="remember" name="remember">
        <label class="form-check-label" for="remember">Lembrar de mim</label>
      </div>
      <a href="{{ route('password.request') }}">
        <p class="mb-0">Esqueceu a senha?</p>
      </a>
    </div>
  </div>
  <button class="btn btn-primary d-grid w-100">Entrar</button>
</form>

<p class="text-center">
  <span>Organiza rifas?</span>
  <a href="{{ route('register') }}"><span>Crie sua conta</span></a>
</p>
@endsection
