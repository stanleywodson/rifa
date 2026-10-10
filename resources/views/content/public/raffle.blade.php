@php
$configData = Helper::appClasses();
$percent = $raffle->total_numbers ? round($sold * 100 / $raffle->total_numbers, 1) : 0;
$draw = $raffle->lastDraw;
@endphp

@extends('layouts/layoutMaster')

@section('title', $raffle->title)
@section('description', \Illuminate\Support\Str::limit($raffle->summary ?: strip_tags($raffle->description), 155))

@section('page-meta')
@include('content.public._meta', [
  'title' => $raffle->title . ' — ' . $tenant->name,
  'description' => \Illuminate\Support\Str::limit($raffle->summary ?: ($raffle->prize?->title . ' por ' . $raffle->priceFormatted() . ' o número'), 155),
  'image' => $raffle->coverUrl(),
])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/front-page-help-center.scss'])
<style>
  .raffle-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(64px, 1fr)); gap: .5rem; }
  .raffle-grid .btn { padding: .4rem 0; font-variant-numeric: tabular-nums; }
  .raffle-cart { position: sticky; bottom: 0; z-index: 10; }
</style>
@endsection

@section('page-script')
@vite(['resources/assets/js/saas-raffle-grid.js'])
@endsection

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="row g-6">
      <!-- Prêmio -->
      <div class="col-lg-7">
        <div class="card mb-6">
          @if ($raffle->media->isNotEmpty())
            <div id="raffleGallery" class="carousel slide" data-bs-ride="carousel">
              @if ($raffle->media->count() > 1)
                <div class="carousel-indicators">
                  @foreach ($raffle->media as $i => $media)
                    <button type="button" data-bs-target="#raffleGallery" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}"></button>
                  @endforeach
                </div>
              @endif
              <div class="carousel-inner rounded-top">
                @foreach ($raffle->media as $i => $media)
                  <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    <img class="d-block w-100 object-fit-cover" src="{{ $media->url() }}" alt="{{ $raffle->prize?->title }}" style="max-height: 440px">
                  </div>
                @endforeach
              </div>
              @if ($raffle->media->count() > 1)
                <a class="carousel-control-prev" href="#raffleGallery" role="button" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></a>
                <a class="carousel-control-next" href="#raffleGallery" role="button" data-bs-slide="next"><span class="carousel-control-next-icon"></span></a>
              @endif
            </div>
          @endif
          <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
              {!! $raffle->status->badge() !!}
              @if ($tenant->isVerified())
                <span class="badge bg-label-success"><i class="ti ti-rosette-discount-check ti-xs me-1"></i>Organizador verificado</span>
              @endif
              @if ($raffle->authorization_number)
                <span class="badge bg-label-secondary">Autorização {{ $raffle->authorization_number }}</span>
              @endif
            </div>
            <h3 class="mb-2">{{ $raffle->title }}</h3>
            <p class="mb-4">por <a href="{{ route('public.tenant', $tenant) }}" class="fw-medium">{{ $tenant->name }}</a></p>
            @if ($raffle->prize)
              <div class="d-flex align-items-center mb-4">
                <div class="avatar me-3"><span class="avatar-initial rounded bg-label-primary"><i class="ti ti-gift ti-md"></i></span></div>
                <div>
                  <h6 class="mb-0">Prêmio: {{ $raffle->prize->title }}</h6>
                  @if ($raffle->prize->estimated_value_cents)<small class="text-muted">Valor estimado {{ money_br($raffle->prize->estimated_value_cents) }}</small>@endif
                </div>
              </div>
            @endif
            @if ($raffle->description)
              <div class="raffle-description">{!! $raffle->description !!}</div>
            @endif
          </div>
        </div>

        <!-- Regulamento -->
        <div class="accordion mb-6" id="raffleInfo">
          <div class="card accordion-item">
            <h2 class="accordion-header">
              <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#regulation">
                <i class="ti ti-file-text me-2"></i>Regulamento
              </button>
            </h2>
            <div id="regulation" class="accordion-collapse collapse" data-bs-parent="#raffleInfo">
              <div class="accordion-body" style="white-space: pre-line">{{ $raffle->regulation }}</div>
            </div>
          </div>
          <div class="card accordion-item">
            <h2 class="accordion-header">
              <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#drawRules">
                <i class="ti ti-shield-check me-2"></i>Como será o sorteio
              </button>
            </h2>
            <div id="drawRules" class="accordion-collapse collapse" data-bs-parent="#raffleInfo">
              <div class="accordion-body">
                <p class="mb-2"><strong>Método:</strong> {{ $raffle->draw_method->label() }}</p>
                <p class="mb-2"><strong>Se o número sorteado não tiver sido vendido:</strong> {{ $raffle->unsold_rule->label() }}</p>
                @if ($raffle->commit_hash)
                  <p class="mb-0"><strong>Commit publicado:</strong> <code class="small text-break">{{ $raffle->commit_hash }}</code><br>
                    <small class="text-muted">Hash SHA-256 da semente secreta gerada na aprovação. A semente é revelada no sorteio para que qualquer pessoa confira.</small></p>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Compra -->
      <div class="col-lg-5">
        <div class="card mb-6">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <small class="text-muted d-block">Valor por número</small>
                <span class="h3 text-primary fw-extrabold mb-0">{{ $raffle->priceFormatted() }}</span>
              </div>
              @if ($raffle->draw_at)
                <div class="text-end">
                  <small class="text-muted d-block">Sorteio</small>
                  <span class="fw-medium text-heading">{{ $raffle->draw_at->format('d/m/Y H:i') }}</span>
                </div>
              @endif
            </div>
            <div class="d-flex justify-content-between small mb-1">
              <span>{{ number_format($sold, 0, ',', '.') }} de {{ number_format($raffle->total_numbers, 0, ',', '.') }} vendidos</span>
              <span class="fw-medium">{{ $percent }}%</span>
            </div>
            <div class="progress mb-6" style="height: 10px">
              <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%"></div>
            </div>

            @if ($raffle->isOnSale())
              <div class="alert alert-info small mb-6">
                <i class="ti ti-clock me-1"></i>Vendas até <strong>{{ $raffle->sales_end_at->format('d/m/Y H:i') }}</strong>.
                Os números ficam reservados por {{ config('saas.reservation_ttl') }} minutos para pagamento via Pix.
              </div>
              <form action="{{ route('public.checkout', [$tenant, $raffle]) }}" method="GET">
                <label class="form-label" for="quantidade">Números aleatórios</label>
                <div class="input-group mb-2">
                  <button class="btn btn-outline-primary" type="button" data-qty="-1"><i class="ti ti-minus"></i></button>
                  <input type="number" class="form-control text-center" id="quantidade" name="quantidade" min="{{ $raffle->min_numbers_per_order }}" max="{{ config('saas.max_numbers_per_order') }}" value="{{ max(5, $raffle->min_numbers_per_order) }}">
                  <button class="btn btn-outline-primary" type="button" data-qty="1"><i class="ti ti-plus"></i></button>
                </div>
                <div class="d-flex gap-2 mb-4">
                  @foreach ([5, 10, 25, 50] as $q)
                    <button type="button" class="btn btn-sm btn-label-primary flex-fill" data-qty-set="{{ $q }}">+{{ $q }}</button>
                  @endforeach
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-arrows-shuffle me-2"></i>Comprar números aleatórios</button>
              </form>
              <div class="divider my-4"><div class="divider-text">ou escolha na grade abaixo</div></div>
            @elseif ($raffle->isBlockedByPayments())
              <div class="alert alert-warning mb-0"><i class="ti ti-clock-pause me-1"></i>As vendas desta rifa estão temporariamente indisponíveis. Tente novamente mais tarde.</div>
            @elseif ($raffle->status->value === 'published')
              <div class="alert alert-warning mb-0">As vendas desta rifa ainda não estão disponíveis.</div>
            @elseif (in_array($raffle->status->value, ['drawn', 'completed']) && $draw?->drawn_number !== null)
              <div class="text-center p-4 bg-label-success rounded">
                <small class="d-block">Número contemplado</small>
                <span class="display-5 fw-bold">{{ $raffle->formatNumber($draw->drawn_number) }}</span>
                <a href="{{ route('public.draw', [$tenant, $raffle]) }}" class="btn btn-success w-100 mt-4"><i class="ti ti-certificate me-2"></i>Ver prova do sorteio</a>
              </div>
            @else
              <div class="alert alert-secondary mb-0">Vendas encerradas. Aguarde o sorteio.</div>
            @endif
            <a href="{{ route('public.my-tickets') }}" class="btn btn-text-secondary w-100 mt-2"><i class="ti ti-search me-1"></i>Consultar meus números</a>
          </div>
        </div>
        <div class="card">
          <div class="card-body d-flex gap-2">
            @php $shareText = urlencode($raffle->title . ' — ' . $raffle->publicUrl()); @endphp
            <a class="btn btn-label-success flex-fill" target="_blank" rel="noopener" href="https://wa.me/?text={{ $shareText }}"><i class="ti ti-brand-whatsapp me-1"></i>WhatsApp</a>
            <a class="btn btn-label-primary flex-fill" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($raffle->publicUrl()) }}"><i class="ti ti-brand-facebook me-1"></i>Facebook</a>
            <button type="button" class="btn btn-label-secondary flex-fill" data-copy="{{ $raffle->publicUrl() }}"><i class="ti ti-copy me-1"></i>Copiar link</button>
          </div>
        </div>
      </div>
    </div>

    @if ($raffle->tickets_generated_at)
    <!-- Grade de números -->
    <div class="card mt-6" id="raffleGridCard"
      data-grid-url="{{ route('public.raffle.grid', [$tenant, $raffle]) }}"
      data-checkout-url="{{ route('public.checkout', [$tenant, $raffle]) }}"
      data-price="{{ $raffle->price_cents }}"
      data-max="{{ config('saas.max_numbers_per_order') }}"
      data-start="{{ $raffle->number_start }}"
      data-last="{{ $raffle->lastNumber() }}"
      data-size="{{ $gridPageSize }}"
      data-on-sale="{{ $raffle->isOnSale() ? 1 : 0 }}"
      data-key="raffle-{{ $raffle->id }}">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h5 class="card-title mb-0">Escolha seus números</h5>
        <div class="d-flex flex-wrap gap-3 small">
          <span><span class="badge badge-dot bg-secondary me-1"></span>Livre</span>
          <span><span class="badge badge-dot bg-warning me-1"></span>Reservado</span>
          <span><span class="badge badge-dot bg-success me-1"></span>Pago</span>
          <span><span class="badge badge-dot bg-primary me-1"></span>Selecionado</span>
        </div>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
          <div class="btn-group btn-group-sm flex-wrap" role="group" data-grid-pages></div>
          <div class="ms-auto input-group input-group-sm" style="max-width: 220px">
            <input type="number" class="form-control" placeholder="Ir para o número" data-grid-search>
            <button class="btn btn-outline-primary" type="button" data-grid-search-btn><i class="ti ti-search"></i></button>
          </div>
        </div>
        <div class="raffle-grid" data-grid>
          <div class="text-center py-6 w-100"><div class="spinner-border text-primary" role="status"></div></div>
        </div>
      </div>
      @if ($raffle->isOnSale())
      <div class="card-footer border-top raffle-cart bg-body d-none" data-cart>
        <div class="d-flex flex-wrap align-items-center gap-3 pt-4">
          <div class="flex-grow-1">
            <span class="fw-medium text-heading" data-cart-count>0</span> número(s) selecionado(s) ·
            <span class="fw-bold text-primary" data-cart-total>R$ 0,00</span>
            <div class="small text-muted text-truncate" data-cart-numbers></div>
          </div>
          <button type="button" class="btn btn-label-secondary" data-cart-clear>Limpar</button>
          <button type="button" class="btn btn-primary" data-cart-go>Continuar <i class="ti ti-arrow-right ms-1"></i></button>
        </div>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
@endsection
