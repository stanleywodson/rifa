@php use App\Domain\Tenants\Enums\KycStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Configurações')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js'])
@endsection

{{-- Base: pages/pages-account-settings-* --}}
@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
        @foreach (['organizacao' => ['ti-building-store', 'Organização'], 'verificacao' => ['ti-id-badge-2', 'Verificação (KYC)'], 'recebimentos' => ['ti-building-bank', 'Recebimentos'], 'plano' => ['ti-package', 'Plano']] as $key => [$icon, $label])
          <li class="nav-item"><button type="button" class="nav-link {{ $tab === $key ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}"><i class="ti {{ $icon }} ti-sm me-1_5"></i>{{ $label }}</button></li>
        @endforeach
      </ul>
    </div>

    <div class="tab-content p-0 shadow-none bg-transparent">
      <!-- Organização -->
      <div class="tab-pane fade {{ $tab === 'organizacao' ? 'show active' : '' }}" id="tab-organizacao">
        <div class="card mb-6">
          <div class="card-header"><h5 class="card-title mb-0">Dados da organização</h5></div>
          <div class="card-body pt-4">
            <form method="POST" action="{{ route('painel.configuracoes.update') }}">
              @csrf @method('PUT')
              <fieldset @disabled(!$isOwner)>
              <div class="row g-6">
                <div class="col-md-6">
                  <label for="name" class="form-label">Nome</label>
                  <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                </div>
                <div class="col-md-6">
                  <label for="document" class="form-label">CPF/CNPJ</label>
                  <input class="form-control" type="text" id="document" name="document" data-mask="document" value="{{ old('document', $tenant->document) }}" @disabled($tenant->isVerified())>
                  @if ($tenant->isVerified())<small class="text-muted">Documento verificado — alteração somente via suporte.</small>@endif
                </div>
                <div class="col-md-6">
                  <label for="email" class="form-label">E-mail de contato</label>
                  <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $tenant->email) }}" required>
                </div>
                <div class="col-md-6">
                  <label for="phone" class="form-label">Telefone</label>
                  <input class="form-control" type="text" id="phone" name="phone" data-mask="phone" value="{{ old('phone', $tenant->phone) }}" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Endereço da página</label>
                  <div class="input-group"><span class="input-group-text">{{ url('/') }}/</span><input class="form-control" value="{{ $tenant->slug }}" disabled></div>
                </div>
                <div class="col-md-6">
                  <label for="instagram" class="form-label">Instagram</label>
                  <div class="input-group"><span class="input-group-text">@</span><input class="form-control" id="instagram" name="instagram" value="{{ old('instagram', ltrim($tenant->settings['instagram'] ?? '', '@')) }}"></div>
                </div>
                <div class="col-12">
                  <label for="about" class="form-label">Apresentação (página pública)</label>
                  <textarea class="form-control" id="about" name="about" rows="3" maxlength="1000">{{ old('about', $tenant->settings['about'] ?? '') }}</textarea>
                </div>
              </div>
              <div class="mt-6"><button type="submit" class="btn btn-primary me-3">Salvar</button></div>
              </fieldset>
            </form>
          </div>
        </div>
      </div>

      <!-- KYC -->
      <div class="tab-pane fade {{ $tab === 'verificacao' ? 'show active' : '' }}" id="tab-verificacao">
        <div class="card mb-6">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Verificação do organizador</h5>
            {!! $tenant->kyc_status->badge() !!}
          </div>
          <div class="card-body">
            @if ($tenant->kyc_status === KycStatus::Rejected && $tenant->kyc_notes)
              <div class="alert alert-danger">{{ $tenant->kyc_notes }}</div>
            @elseif ($tenant->kyc_status === KycStatus::Approved)
              <div class="alert alert-success mb-6">Organização verificada em {{ $tenant->kyc_reviewed_at?->format('d/m/Y') }}.</div>
            @else
              <p>Envie um documento de identidade do responsável e, para pessoa jurídica, o contrato social ou estatuto. Os arquivos são privados e usados apenas para verificação.</p>
            @endif
            @if ($isOwner)
              <form method="POST" action="{{ route('painel.configuracoes.documents') }}" enctype="multipart/form-data" class="row g-4 mb-6">
                @csrf
                <div class="col-md-5">
                  <select name="type" class="form-select" required>
                    @foreach ($documentTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                  </select>
                </div>
                <div class="col-md-5"><input type="file" name="file" class="form-control" accept="application/pdf,image/jpeg,image/png" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Enviar</button></div>
              </form>
            @endif
            <div class="table-responsive">
              <table class="table">
                <thead class="border-top"><tr><th>Documento</th><th>Arquivo</th><th>Enviado em</th></tr></thead>
                <tbody>
                  @forelse ($tenant->kycDocuments as $doc)
                    <tr><td>{{ $doc->typeLabel() }}</td><td>{{ $doc->original_name }}</td><td>{{ $doc->created_at->format('d/m/Y H:i') }}</td></tr>
                  @empty
                    <tr><td colspan="3" class="text-muted text-center">Nenhum documento enviado.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Recebimentos -->
      <div class="tab-pane fade {{ $tab === 'recebimentos' ? 'show active' : '' }}" id="tab-recebimentos">
        <div class="card mb-6">
          <div class="card-header"><h5 class="card-title mb-0">Conta de recebimento (split Pix)</h5></div>
          <div class="card-body">
            @if ($tenant->asaas_wallet_id && !$tenant->hasPaymentAccount())
              <div class="alert alert-warning">A conta de recebimento atual foi criada em outro ambiente ({{ $tenant->payment_gateway ?? 'desconhecido' }}) e não é válida no gateway configurado. Crie a conta novamente abaixo.</div>
            @endif
            @if ($tenant->hasPaymentAccount())
              <div class="alert alert-success d-flex align-items-center mb-0">
                <span class="alert-icon rounded"><i class="ti ti-building-bank"></i></span>
                <span>Conta configurada. Cada venda é dividida automaticamente: {{ 100 - $tenant->feePercent() }}% para você e {{ $tenant->feePercent() }}% de taxa da plataforma.<br><small class="text-muted">Carteira {{ $tenant->asaas_wallet_id }}</small></span>
              </div>
            @else
              <p>As vendas são cobradas via Pix e o valor é repassado diretamente para a sua subconta no gateway {{ $gateway === 'asaas' ? 'Asaas' : '(modo de testes)' }} — a plataforma não retém o dinheiro do prêmio.</p>
              @if ($isOwner)
                <form method="POST" action="{{ route('painel.configuracoes.payments') }}" class="row g-6">
                  @csrf
                  <div class="col-md-6"><label class="form-label" for="pay_email">E-mail da conta</label><input type="email" class="form-control" id="pay_email" name="email" value="{{ old('email', $tenant->email) }}" required></div>
                  <div class="col-md-6"><label class="form-label" for="pay_phone">Celular</label><input type="text" class="form-control" id="pay_phone" name="phone" data-mask="phone" value="{{ old('phone', $tenant->phone) }}" required></div>
                  @if (strlen((string) $tenant->document) === 14)
                    <div class="col-md-6">
                      <label class="form-label" for="company_type">Tipo de empresa</label>
                      <select class="form-select" id="company_type" name="company_type">
                        @foreach (['ASSOCIATION' => 'Associação / ONG / Igreja', 'MEI' => 'MEI', 'LIMITED' => 'Limitada', 'INDIVIDUAL' => 'Individual'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                      </select>
                    </div>
                  @else
                    <div class="col-md-6"><label class="form-label" for="birth_date">Data de nascimento</label><input type="date" class="form-control" id="birth_date" name="birth_date" value="{{ old('birth_date') }}"></div>
                  @endif
                  <div class="col-md-6"><label class="form-label" for="income_value">Faturamento/renda mensal (R$)</label><input type="number" step="0.01" class="form-control" id="income_value" name="income_value" value="{{ old('income_value') }}" required></div>
                  <div class="col-md-4"><label class="form-label" for="postal_code">CEP</label><input type="text" class="form-control" id="postal_code" name="postal_code" data-mask="cep" value="{{ old('postal_code') }}" required></div>
                  <div class="col-md-6"><label class="form-label" for="address">Endereço</label><input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" required></div>
                  <div class="col-md-2"><label class="form-label" for="address_number">Número</label><input type="text" class="form-control" id="address_number" name="address_number" value="{{ old('address_number') }}" required></div>
                  <div class="col-md-6"><label class="form-label" for="province">Bairro</label><input type="text" class="form-control" id="province" name="province" value="{{ old('province') }}" required></div>
                  <div class="col-12"><button class="btn btn-primary">Criar conta de recebimento</button></div>
                </form>
              @endif
            @endif
          </div>
        </div>
      </div>

      <!-- Plano -->
      <div class="tab-pane fade {{ $tab === 'plano' ? 'show active' : '' }}" id="tab-plano">
        <div class="card mb-6">
          <div class="card-header"><h5 class="card-title mb-0">Plano atual</h5></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <h6 class="mb-1">{{ $tenant->plan?->name ?? 'Sem plano' }}</h6>
                <p>{{ $tenant->plan?->price_cents ? money_br($tenant->plan->price_cents) . '/mês' : 'Gratuito' }}</p>
                <ul class="list-unstyled">
                  <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>Taxa por venda: {{ $tenant->feePercent() }}%</li>
                  <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>Rifas ativas: {{ $tenant->plan?->limit('active_raffles') ?? 'ilimitadas' }}</li>
                  <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>Números por rifa: até {{ number_format($tenant->plan?->limit('max_numbers') ?? config('saas.max_numbers'), 0, ',', '.') }}</li>
                </ul>
              </div>
              <div class="col-md-6">
                <div class="alert alert-info mb-0">A troca de plano é feita pelo suporte durante o piloto. A cobrança recorrente da assinatura será habilitada na próxima versão.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
