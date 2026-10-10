@extends('layouts/authCover', ['illustration' => 'auth-reset-password-illustration'])

@section('title', 'Redefinir senha')

@section('auth-form')
<h4 class="mb-1">Redefinir senha 🔒</h4>
<p class="mb-6">A nova senha deve ser diferente das anteriores</p>
<form class="mb-6" action="{{ route('password.update') }}" method="POST">
  @csrf
  <input type="hidden" name="token" value="{{ $request->route('token') }}">
  <div class="mb-6">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $request->email) }}" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="mb-6 form-password-toggle">
    <label class="form-label" for="password">Nova senha</label>
    <div class="input-group input-group-merge">
      <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" required />
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="mb-6 form-password-toggle">
    <label class="form-label" for="password_confirmation">Confirme a senha</label>
    <div class="input-group input-group-merge">
      <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required />
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
  </div>
  <button class="btn btn-primary d-grid w-100 mb-6">Salvar nova senha</button>
  <div class="text-center">
    <a href="{{ route('login') }}" class="d-flex justify-content-center">
      <i class="ti ti-chevron-left scaleX-n1-rtl me-1_5"></i>
      Voltar para o login
    </a>
  </div>
</form>
@endsection
