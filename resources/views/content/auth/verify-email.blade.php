@extends('layouts/authCover', ['illustration' => 'auth-verify-email-illustration'])

@section('title', 'Verifique seu e-mail')

@section('auth-form')
<h4 class="mb-1">Verifique seu e-mail ✉️</h4>
<p class="text-start mb-0">
  Enviamos um link de confirmação para <span class="fw-medium text-heading">{{ auth()->user()->email }}</span>.
  Acesse o link para ativar sua conta.
</p>
@if (session('status') === 'verification-link-sent')
  <div class="alert alert-success mt-4 mb-0">Um novo link de verificação foi enviado.</div>
@endif
<form method="POST" action="{{ route('verification.send') }}" class="my-6">
  @csrf
  <button class="btn btn-primary w-100">Reenviar e-mail</button>
</form>
<form method="POST" action="{{ route('logout') }}" class="text-center">
  @csrf
  <button type="submit" class="btn btn-link">Sair</button>
</form>
@endsection
