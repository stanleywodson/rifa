@php use App\Domain\Raffles\Enums\DrawMethod; @endphp
@extends('layouts/layoutMaster')

@section('title', 'Sorteio — ' . $raffle->title)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js'])
@endsection

{{-- Base: form-layouts-vertical --}}
@section('content')
<h4 class="mb-1">Realizar sorteio</h4>
<p class="mb-6">{{ $raffle->title }} · {{ $paid }} números pagos de {{ $raffle->total_numbers }}</p>

<div class="row g-6">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">{{ $raffle->draw_method->label() }}</h5></div>
      <div class="card-body">
        <form method="POST" action="{{ route('painel.rifas.draw.store', $raffle) }}" data-confirm="Confirmar o sorteio?" data-confirm-text="O resultado é definitivo e ficará registrado na prova pública." data-confirm-button="Sortear">
          @csrf
          @if ($raffle->draw_method === DrawMethod::Manual)
            <div class="mb-6">
              <label class="form-label" for="number">Número sorteado ao vivo</label>
              <input type="number" class="form-control" id="number" name="number" min="{{ $raffle->number_start }}" max="{{ $raffle->lastNumber() }}" value="{{ old('number') }}" required>
            </div>
            <div class="mb-6">
              <label class="form-label" for="evidence_url">Link da gravação / transmissão</label>
              <input type="url" class="form-control" id="evidence_url" name="evidence_url" value="{{ old('evidence_url') }}" placeholder="https://youtube.com/..." required>
            </div>
            <div class="mb-6">
              <label class="form-label" for="notes">Observações (testemunhas, local)</label>
              <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>
          @else
            <div class="mb-6">
              <label class="form-label" for="concurso">Concurso da Loteria Federal</label>
              <input type="text" class="form-control" id="concurso" name="concurso" value="{{ old('concurso') }}" placeholder="Ex.: 5912" required>
              <small class="text-muted">O resultado é consultado automaticamente no portal da Caixa.</small>
            </div>
            <div class="accordion mb-6" id="manualFederal">
              <div class="accordion-item card">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manualFederalBody">Portal da Caixa indisponível? Informar o resultado manualmente</button>
                </h2>
                <div id="manualFederalBody" class="accordion-collapse collapse" data-bs-parent="#manualFederal">
                  <div class="accordion-body row g-4">
                    <div class="col-md-5">
                      <label class="form-label" for="premio">1º prêmio (bilhete)</label>
                      <input type="text" class="form-control" id="premio" name="premio" value="{{ old('premio') }}">
                    </div>
                    <div class="col-md-7">
                      <label class="form-label" for="evidence_url">Link oficial do resultado</label>
                      <input type="url" class="form-control" id="evidence_url" name="evidence_url" value="{{ old('evidence_url') }}">
                    </div>
                    <small class="text-muted">Resultados informados manualmente ficam sinalizados na prova pública.</small>
                  </div>
                </div>
              </div>
            </div>
          @endif
          <div class="form-check mb-6">
            <input class="form-check-input" type="checkbox" id="confirm" name="confirm" value="1" required>
            <label class="form-check-label" for="confirm">Confirmo que o sorteio segue o regulamento publicado e que o resultado será definitivo.</label>
          </div>
          <button class="btn btn-success"><i class="ti ti-trophy me-1"></i>Sortear</button>
          <a href="{{ route('painel.rifas.show', $raffle) }}" class="btn btn-label-secondary ms-2">Voltar</a>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Regras congeladas</h5></div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="mb-3"><span class="h6 me-1">Faixa:</span>{{ $raffle->formatNumber($raffle->number_start) }} a {{ $raffle->formatNumber($raffle->lastNumber()) }}</li>
          <li class="mb-3"><span class="h6 me-1">Fórmula:</span>
            @if ($raffle->draw_method === DrawMethod::Federal)
              (1º prêmio mod {{ $raffle->total_numbers }}) + {{ $raffle->number_start }}
            @elseif ($raffle->draw_method === DrawMethod::CommitReveal)
              (sha256(semente|1º prêmio) mod {{ $raffle->total_numbers }}) + {{ $raffle->number_start }}
            @else
              número informado ao vivo
            @endif
          </li>
          <li class="mb-3"><span class="h6 me-1">Não vendido:</span>{{ $raffle->unsold_rule->label() }}</li>
          @if ($raffle->commit_hash)<li><span class="h6 me-1">Commit:</span><code class="small text-break">{{ $raffle->commit_hash }}</code></li>@endif
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
