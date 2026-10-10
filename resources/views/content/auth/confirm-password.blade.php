@extends('layouts/authCover', ['illustration' => 'auth-login-illustration'])

@section('title', 'Confirmar senha')

@section('auth-form')
<h4 class="mb-1">Confirme sua senha 🔐</h4>
<p class="mb-6">Por segurança, confirme sua senha para continuar.</p>
<form action="{{ route('password.confirm') }}" method="POST">
  @csrf
  <div class="mb-6 form-password-toggle">
    <label class="form-label" for="password">Senha</label>
    <div class="input-group input-group-merge">
      <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" required autofocus />
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <button class="btn btn-primary d-grid w-100">Confirmar</button>
</form>
@endsection
