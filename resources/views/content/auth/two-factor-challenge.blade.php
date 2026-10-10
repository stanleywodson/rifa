@extends('layouts/authCover', ['illustration' => 'auth-two-step-illustration'])

@section('title', 'Verificação em duas etapas')

@section('auth-form')
<h4 class="mb-1">Verificação em duas etapas 💬</h4>
<p class="text-start mb-6">Informe o código de 6 dígitos do seu aplicativo autenticador ou um código de recuperação.</p>
<form action="{{ route('two-factor.login') }}" method="POST">
  @csrf
  <div class="mb-6">
    <label class="form-label" for="code">Código</label>
    <input type="text" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror" id="code" name="code" maxlength="6" autofocus>
    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="mb-6">
    <label class="form-label" for="recovery_code">ou código de recuperação</label>
    <input type="text" class="form-control" id="recovery_code" name="recovery_code">
  </div>
  <button class="btn btn-primary d-grid w-100 mb-6">Verificar</button>
</form>
@endsection
