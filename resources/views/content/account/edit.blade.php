@extends('layouts/layoutMaster')

@section('title', 'Minha conta')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js'])
@endsection

{{-- Base: pages/pages-account-settings-account e -security; formulários enviados ao Fortify --}}
@section('content')
@php
$status = session('status');
$messages = [
  'profile-information-updated' => 'Perfil atualizado.',
  'password-updated' => 'Senha alterada.',
  'two-factor-authentication-enabled' => 'Escaneie o QR Code e confirme o código para ativar a verificação em duas etapas.',
  'two-factor-authentication-confirmed' => 'Verificação em duas etapas ativada.',
  'two-factor-authentication-disabled' => 'Verificação em duas etapas desativada.',
  'recovery-codes-generated' => 'Novos códigos de recuperação gerados.',
];
@endphp
@if ($status && isset($messages[$status]))
  <div class="alert alert-success mb-6">{{ $messages[$status] }}</div>
@endif

<div class="nav-align-top">
  <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
    <li class="nav-item"><button type="button" class="nav-link {{ $tab === 'perfil' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-perfil"><i class="ti ti-user ti-sm me-1_5"></i>Perfil</button></li>
    <li class="nav-item"><button type="button" class="nav-link {{ $tab === 'seguranca' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-seguranca"><i class="ti ti-lock ti-sm me-1_5"></i>Segurança</button></li>
  </ul>
</div>

<div class="tab-content p-0 shadow-none bg-transparent">
  <div class="tab-pane fade {{ $tab === 'perfil' ? 'show active' : '' }}" id="tab-perfil">
    <div class="card mb-6">
      <div class="card-body">
        <div class="d-flex align-items-start align-items-sm-center gap-6 mb-6">
          <div class="avatar avatar-xl"><span class="avatar-initial rounded bg-label-primary">{{ $user->initials() }}</span></div>
          <div><h5 class="mb-0">{{ $user->name }}</h5><small>{{ $user->email }}</small></div>
        </div>
        <form method="POST" action="{{ route('user-profile-information.update') }}">
          @csrf @method('PUT')
          <div class="row g-6">
            <div class="col-md-6">
              <label class="form-label" for="name">Nome</label>
              <input class="form-control @error('name', 'updateProfileInformation') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
              @error('name', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">E-mail</label>
              <input type="email" class="form-control @error('email', 'updateProfileInformation') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
              @error('email', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="mt-6"><button type="submit" class="btn btn-primary">Salvar</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade {{ $tab === 'seguranca' ? 'show active' : '' }}" id="tab-seguranca">
    <div class="card mb-6">
      <h5 class="card-header">Alterar senha</h5>
      <div class="card-body pt-1">
        <form method="POST" action="{{ route('user-password.update') }}">
          @csrf @method('PUT')
          <div class="row g-6">
            <div class="col-md-6 form-password-toggle">
              <label class="form-label" for="current_password">Senha atual</label>
              <div class="input-group input-group-merge">
                <input class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" type="password" name="current_password" id="current_password" required>
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              @error('current_password', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="row g-6 mt-0">
            <div class="col-md-6 form-password-toggle">
              <label class="form-label" for="password">Nova senha</label>
              <div class="input-group input-group-merge">
                <input class="form-control @error('password', 'updatePassword') is-invalid @enderror" type="password" id="password" name="password" required>
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              @error('password', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 form-password-toggle">
              <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
              <div class="input-group input-group-merge">
                <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required>
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
            </div>
          </div>
          <div class="mt-6"><button type="submit" class="btn btn-primary">Alterar senha</button></div>
        </form>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-body">
        <h5 class="mb-6">Verificação em duas etapas</h5>
        @if (!$user->two_factor_secret)
          <h5 class="mb-4 text-body">A verificação em duas etapas não está ativada.</h5>
          <p class="w-75">Adicione uma camada extra de segurança exigindo um código do aplicativo autenticador (Google Authenticator, Authy) ao entrar.
            @if ($user->isPlatformAdmin())<strong>Obrigatória para administradores.</strong>@endif</p>
          <form method="POST" action="{{ route('two-factor.enable') }}">@csrf<button class="btn btn-primary mt-2">Ativar</button></form>
        @elseif (!$user->two_factor_confirmed_at)
          <p>Escaneie o QR Code com o aplicativo autenticador e informe o código para confirmar.</p>
          <div class="mb-4">{!! $user->twoFactorQrCodeSvg() !!}</div>
          <form method="POST" action="{{ route('two-factor.confirm') }}" class="row g-4">
            @csrf
            <div class="col-md-4"><input type="text" class="form-control @error('code', 'confirmTwoFactorAuthentication') is-invalid @enderror" name="code" inputmode="numeric" maxlength="6" placeholder="000000" required></div>
            <div class="col-md-4"><button class="btn btn-primary">Confirmar</button></div>
            @error('code', 'confirmTwoFactorAuthentication')<div class="col-12 text-danger small">{{ $message }}</div>@enderror
          </form>
        @else
          <div class="alert alert-success">Verificação em duas etapas ativa.</div>
          <h6>Códigos de recuperação</h6>
          <p class="small">Guarde em local seguro. Cada código pode ser usado uma vez.</p>
          <div class="bg-lighter rounded p-4 mb-4"><code>{!! implode('<br>', array_map('e', $user->recoveryCodes())) !!}</code></div>
          <div class="d-flex gap-2">
            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">@csrf<button class="btn btn-label-secondary">Gerar novos códigos</button></form>
            <form method="POST" action="{{ route('two-factor.disable') }}">@csrf @method('DELETE')<button class="btn btn-label-danger">Desativar</button></form>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
