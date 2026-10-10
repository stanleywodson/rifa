@extends('layouts/authCover', ['illustration' => 'auth-forgot-password-illustration'])

@section('title', 'Recuperar senha')

@section('auth-form')
<h4 class="mb-1">Esqueceu a senha? 🔒</h4>
<p class="mb-6">Informe seu e-mail e enviaremos um link para redefinir a senha</p>
<form class="mb-6" action="{{ route('password.email') }}" method="POST">
  @csrf
  <div class="mb-6">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" autofocus required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <button class="btn btn-primary d-grid w-100">Enviar link</button>
</form>
<div class="text-center">
  <a href="{{ route('login') }}" class="d-flex justify-content-center">
    <i class="ti ti-chevron-left scaleX-n1-rtl me-1_5"></i>
    Voltar para o login
  </a>
</div>
@endsection
