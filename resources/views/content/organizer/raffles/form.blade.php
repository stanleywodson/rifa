@php
$editing = $raffle->exists;
$stepFields = [
  ['title', 'summary', 'description'],
  ['prize_title', 'prize_description', 'prize_value', 'images', 'images.*'],
  ['total_numbers', 'number_start', 'price', 'min_numbers_per_order'],
  ['sales_end_at', 'draw_at', 'draw_method', 'unsold_rule'],
  ['authorization_number', 'authorization_doc', 'regulation'],
];
$errorStep = collect($stepFields)->search(fn($fields) => $errors->hasAny($fields));
$prize = $raffle->prize;
$dt = fn($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d H:i') : '';
@endphp

@extends('layouts/layoutMaster')

@section('title', $editing ? 'Editar rifa' : 'Nova rifa')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
  'resources/assets/vendor/libs/quill/typography.scss',
  'resources/assets/vendor/libs/quill/katex.scss',
  'resources/assets/vendor/libs/quill/editor.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/dropzone/dropzone.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
  'resources/assets/vendor/libs/cleavejs/cleave.js',
  'resources/assets/vendor/libs/quill/katex.js',
  'resources/assets/vendor/libs/quill/quill.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-forms.js', 'resources/assets/js/saas-raffle-form.js'])
@endsection

{{-- Base: wizard-example/wizard-ex-property-listing + apps/app-ecommerce-product-add --}}
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
  <div class="d-flex flex-column justify-content-center">
    <h4 class="mb-1">{{ $editing ? 'Editar rifa' : 'Nova rifa' }}</h4>
    <p class="mb-0">A rifa fica em rascunho até você enviá-la para análise.</p>
  </div>
  <div class="d-flex align-content-center flex-wrap gap-4">
    <a href="{{ $editing ? route('painel.rifas.show', $raffle) : route('painel.rifas.index') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" form="raffleForm" class="btn btn-primary">Salvar rascunho</button>
  </div>
</div>

@if ($editing && $raffle->review_notes && $raffle->status->value === 'changes_requested')
  <div class="alert alert-info"><h6 class="alert-heading mb-1">Ajustes solicitados pela análise</h6>{{ $raffle->review_notes }}</div>
@endif

<div class="bs-stepper vertical saas-stepper mt-2" data-error-step="{{ $errorStep === false ? 0 : $errorStep }}">
  <div class="bs-stepper-header border-end">
    @foreach ([
      ['#step-info', 'ti-file-description', 'Dados da rifa', 'Título e descrição'],
      ['#step-prize', 'ti-gift', 'Prêmio', 'Detalhes e imagens'],
      ['#step-numbers', 'ti-numbers', 'Números e preço', 'Quantidade e valor'],
      ['#step-draw', 'ti-calendar-event', 'Sorteio', 'Datas e método'],
      ['#step-legal', 'ti-file-certificate', 'Autorização', 'Regulamento e documento'],
    ] as $i => [$target, $icon, $title, $subtitle])
      @if ($i > 0)<div class="line"></div>@endif
      <div class="step" data-target="{{ $target }}">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="ti {{ $icon }} ti-md"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">{{ $title }}</span>
            <span class="bs-stepper-subtitle">{{ $subtitle }}</span>
          </span>
        </button>
      </div>
    @endforeach
  </div>
  <div class="bs-stepper-content">
    <form id="raffleForm" action="{{ $editing ? route('painel.rifas.update', $raffle) : route('painel.rifas.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      @if ($editing) @method('PUT') @endif

      <!-- Dados -->
      <div id="step-info" class="content">
        <div class="row g-6">
          <div class="col-12">
            <label class="form-label" for="title">Título</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $raffle->title) }}" placeholder="Rifa da Moto 0km" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label" for="summary">Resumo <small class="text-muted">(aparece nos cards e no preview do WhatsApp)</small></label>
            <input type="text" class="form-control @error('summary') is-invalid @enderror" id="summary" name="summary" maxlength="300" value="{{ old('summary', $raffle->summary) }}">
            @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="mb-1">Descrição</label>
            <div class="form-control p-0">
              <div class="comment-toolbar border-0 border-bottom" id="descriptionToolbar">
                <div class="d-flex justify-content-start">
                  <span class="ql-formats me-0">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>
                    <button class="ql-link"></button>
                  </span>
                </div>
              </div>
              <div class="comment-editor border-0 pb-6" id="descriptionEditor">{!! clean(old('description', $raffle->description)) !!}</div>
            </div>
            <input type="hidden" name="description" id="description" value="{{ old('description', $raffle->description) }}">
          </div>
          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-label-secondary btn-prev" disabled><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
            <button type="button" class="btn btn-primary btn-next"><span class="align-middle d-sm-inline-block d-none me-sm-2">Próximo</span><i class="ti ti-arrow-right ti-xs"></i></button>
          </div>
        </div>
      </div>

      <!-- Prêmio -->
      <div id="step-prize" class="content">
        <div class="row g-6">
          <div class="col-md-8">
            <label class="form-label" for="prize_title">Prêmio</label>
            <input type="text" class="form-control @error('prize_title') is-invalid @enderror" id="prize_title" name="prize_title" value="{{ old('prize_title', $prize?->title) }}" placeholder="Honda CG 160 0km" required>
            @error('prize_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label" for="prize_value">Valor estimado (R$)</label>
            <input type="text" class="form-control @error('prize_value') is-invalid @enderror" id="prize_value" name="prize_value" data-mask="money" value="{{ old('prize_value', $prize?->estimated_value_cents ? number_format($prize->estimated_value_cents / 100, 2, ',', '.') : '') }}">
            @error('prize_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label" for="prize_description">Detalhes do prêmio</label>
            <textarea class="form-control" id="prize_description" name="prize_description" rows="3">{{ old('prize_description', $prize?->description) }}</textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Imagens do prêmio <small class="text-muted">(até 8, JPG/PNG/WebP, 5 MB cada)</small></label>
            @if ($editing)
              <div class="row g-4 mb-4" id="mediaList">
                @foreach ($raffle->media as $media)
                  <div class="col-6 col-md-3" data-media>
                    <div class="card shadow-none border">
                      <img src="{{ $media->url() }}" class="card-img-top object-fit-cover" style="height: 120px" alt="">
                      <div class="card-body p-2 text-center">
                        <button type="button" class="btn btn-sm btn-text-danger" data-delete-media="{{ route('painel.rifas.media.destroy', [$raffle, $media]) }}"><i class="ti ti-trash me-1"></i>Remover</button>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
              <div class="dropzone needsclick p-0" id="raffleDropzone" data-url="{{ route('painel.rifas.media.store', $raffle) }}">
                <div class="dz-message needsclick">
                  <p class="h4 needsclick pt-3 mb-2">Arraste as imagens aqui</p>
                  <p class="h6 text-muted d-block fw-normal mb-2">ou</p>
                  <span class="note needsclick btn btn-sm btn-label-primary">Selecionar imagens</span>
                </div>
              </div>
            @else
              <input type="file" class="form-control @error('images.*') is-invalid @enderror" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
              @error('images.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
          </div>
          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-label-secondary btn-prev"><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
            <button type="button" class="btn btn-primary btn-next"><span class="align-middle d-sm-inline-block d-none me-sm-2">Próximo</span><i class="ti ti-arrow-right ti-xs"></i></button>
          </div>
        </div>
      </div>

      <!-- Números e preço -->
      <div id="step-numbers" class="content">
        <div class="row g-6">
          <div class="col-md-4">
            <label class="form-label" for="total_numbers">Quantidade de números</label>
            <input type="number" class="form-control @error('total_numbers') is-invalid @enderror" id="total_numbers" name="total_numbers" min="10" max="{{ $maxNumbers }}" value="{{ old('total_numbers', $raffle->total_numbers) }}" required>
            <small class="text-muted">Máximo do seu plano: {{ number_format($maxNumbers, 0, ',', '.') }}</small>
            @error('total_numbers')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label" for="price">Preço por número (R$)</label>
            <input type="text" class="form-control @error('price') is-invalid @enderror" id="price" name="price" data-mask="money" value="{{ old('price', $raffle->price_cents ? number_format($raffle->price_cents / 100, 2, ',', '.') : '') }}" required>
            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label" for="min_numbers_per_order">Mínimo por compra</label>
            <input type="number" class="form-control @error('min_numbers_per_order') is-invalid @enderror" id="min_numbers_per_order" name="min_numbers_per_order" min="1" value="{{ old('min_numbers_per_order', $raffle->min_numbers_per_order) }}" required>
          </div>
          <div class="col-12">
            <label class="form-label d-block">Numeração</label>
            <div class="row g-4">
              @foreach ([0 => 'Começa em 0 (ex.: 00 a 99)', 1 => 'Começa em 1 (ex.: 1 a 100)'] as $value => $label)
                <div class="col-md-6">
                  <div class="form-check custom-option custom-option-basic {{ (int) old('number_start', $raffle->number_start) === $value ? 'checked' : '' }}">
                    <label class="form-check-label custom-option-content" for="numberStart{{ $value }}">
                      <input name="number_start" class="form-check-input" type="radio" value="{{ $value }}" id="numberStart{{ $value }}" @checked((int) old('number_start', $raffle->number_start) === $value)>
                      <span class="custom-option-header"><span class="h6 mb-0">{{ $label }}</span></span>
                    </label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-12">
            <div class="alert alert-primary mb-0" id="revenuePreview">Arrecadação máxima: —</div>
          </div>
          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-label-secondary btn-prev"><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
            <button type="button" class="btn btn-primary btn-next"><span class="align-middle d-sm-inline-block d-none me-sm-2">Próximo</span><i class="ti ti-arrow-right ti-xs"></i></button>
          </div>
        </div>
      </div>

      <!-- Sorteio -->
      <div id="step-draw" class="content">
        <div class="row g-6">
          <div class="col-md-6">
            <label class="form-label" for="sales_end_at">Encerramento das vendas</label>
            <input type="text" class="form-control @error('sales_end_at') is-invalid @enderror" id="sales_end_at" name="sales_end_at" data-datetime value="{{ old('sales_end_at', $dt($raffle->sales_end_at)) }}" required>
            @error('sales_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label" for="draw_at">Data do sorteio</label>
            <input type="text" class="form-control @error('draw_at') is-invalid @enderror" id="draw_at" name="draw_at" data-datetime value="{{ old('draw_at', $dt($raffle->draw_at)) }}" required>
            <small class="text-muted">Para Loteria Federal, use a data do concurso (quartas e sábados).</small>
            @error('draw_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label d-block">Método de sorteio</label>
            <div class="row g-4">
              @foreach ($drawMethods as $method)
                <div class="col-md-4">
                  <div class="form-check custom-option custom-option-basic h-100">
                    <label class="form-check-label custom-option-content" for="method{{ $method->value }}">
                      <input name="draw_method" class="form-check-input" type="radio" value="{{ $method->value }}" id="method{{ $method->value }}" @checked(old('draw_method', $raffle->draw_method?->value) === $method->value)>
                      <span class="custom-option-header"><span class="h6 mb-0">{{ $method->label() }}</span></span>
                      <span class="custom-option-body"><small>{{ [
                        'federal' => 'Número = 1º prêmio da Loteria Federal (mod total). Recomendado.',
                        'commit_reveal' => 'Semente secreta com hash publicado na aprovação + resultado da Federal.',
                        'manual' => 'Sorteio ao vivo, com link da gravação registrado na ata.',
                      ][$method->value] }}</small></span>
                    </label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-12">
            <label class="form-label" for="unsold_rule">Se o número sorteado não tiver sido vendido</label>
            <select class="form-select" id="unsold_rule" name="unsold_rule">
              @foreach ($unsoldRules as $rule)
                <option value="{{ $rule->value }}" @selected(old('unsold_rule', $raffle->unsold_rule?->value) === $rule->value)>{{ $rule->label() }}</option>
              @endforeach
            </select>
            <small class="text-muted">Estas regras ficam congeladas após a publicação.</small>
          </div>
          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-label-secondary btn-prev"><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
            <button type="button" class="btn btn-primary btn-next"><span class="align-middle d-sm-inline-block d-none me-sm-2">Próximo</span><i class="ti ti-arrow-right ti-xs"></i></button>
          </div>
        </div>
      </div>

      <!-- Autorização -->
      <div id="step-legal" class="content">
        <div class="row g-6">
          <div class="col-md-6">
            <label class="form-label" for="authorization_number">Número da autorização</label>
            <input type="text" class="form-control @error('authorization_number') is-invalid @enderror" id="authorization_number" name="authorization_number" value="{{ old('authorization_number', $raffle->authorization_number) }}" placeholder="Certificado / processo SPA">
            @error('authorization_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label" for="authorization_doc">Documento de autorização (PDF/imagem)</label>
            <input type="file" class="form-control @error('authorization_doc') is-invalid @enderror" id="authorization_doc" name="authorization_doc" accept="application/pdf,image/jpeg,image/png">
            @if ($raffle->authorization_doc_path)
              <small><a href="{{ route('painel.rifas.authorization', $raffle) }}" target="_blank"><i class="ti ti-paperclip"></i> documento enviado</a> — envie outro para substituir</small>
            @endif
            @error('authorization_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0" for="regulation">Regulamento</label>
              <button type="button" class="btn btn-sm btn-label-primary" data-regulation-template><i class="ti ti-wand me-1"></i>Gerar modelo</button>
            </div>
            <textarea class="form-control @error('regulation') is-invalid @enderror" id="regulation" name="regulation" rows="12" required>{{ old('regulation', $raffle->regulation) }}</textarea>
            @error('regulation')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-label-secondary btn-prev"><i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i><span class="align-middle d-sm-inline-block d-none">Voltar</span></button>
            <button type="submit" class="btn btn-success">Salvar rascunho</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<input type="hidden" id="tenantName" value="{{ $currentTenant->name }}">
@endsection
