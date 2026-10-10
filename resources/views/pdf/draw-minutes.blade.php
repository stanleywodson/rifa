<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Ata do sorteio — {{ $raffle->title }}</title>
  @include('pdf._style')
</head>
<body>
  <div class="header">
    <h1>Ata do Sorteio</h1>
    <div class="muted">{{ config('app.name') }} · Registro nº {{ $draw->id }} · Gerado em {{ now()->format('d/m/Y H:i') }}</div>
  </div>

  <h2>Rifa</h2>
  <table>
    <tr><th>Título</th><td>{{ $raffle->title }}</td></tr>
    <tr><th>Organizador</th><td>{{ $raffle->tenant->name }} ({{ $raffle->tenant->document }})</td></tr>
    <tr><th>Prêmio</th><td>{{ $raffle->prize?->title }}</td></tr>
    <tr><th>Autorização</th><td>{{ $raffle->authorization_number ?: '—' }}</td></tr>
    <tr><th>Números</th><td>{{ $raffle->formatNumber($raffle->number_start) }} a {{ $raffle->formatNumber($raffle->lastNumber()) }} ({{ $raffle->total_numbers }} números)</td></tr>
    <tr><th>Vendas encerradas em</th><td>{{ $raffle->sales_closed_at?->format('d/m/Y H:i:s') ?? '—' }}</td></tr>
  </table>

  <h2>Resultado</h2>
  <div class="box">
    @if ($draw->drawn_number !== null)
      <div class="muted">Número contemplado</div>
      <div class="big">{{ $raffle->formatNumber($draw->drawn_number) }}</div>
      <div>{{ $draw->customer?->name }} · CPF {{ $draw->customer?->maskedCpf() }}</div>
    @else
      <div class="big">{{ $draw->outcome->label() }}</div>
    @endif
  </div>

  <h2>Método e dados utilizados</h2>
  <table>
    <tr><th>Método</th><td>{{ $draw->method->label() }}</td></tr>
    <tr><th>Fonte</th><td>{{ $draw->source_ref }}</td></tr>
    @foreach ($draw->raw_data as $key => $value)
      <tr><th>{{ str_replace('_', ' ', ucfirst($key)) }}</th><td>{{ is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'sim' : 'não') : $value) }}</td></tr>
    @endforeach
    @if ($draw->seed_revealed)
      <tr><th>Semente revelada</th><td><code>{{ $draw->seed_revealed }}</code></td></tr>
      <tr><th>Commit publicado</th><td><code>{{ $raffle->commit_hash }}</code></td></tr>
    @endif
    <tr><th>Número calculado</th><td>{{ $draw->computed_number }}</td></tr>
    <tr><th>Regra p/ não vendido</th><td>{{ $raffle->unsold_rule->label() }}</td></tr>
    <tr><th>Executado por</th><td>{{ $draw->executor?->name ?? 'Sistema' }} em {{ $draw->executed_at->format('d/m/Y H:i:s') }}</td></tr>
  </table>

  <h2>Verificação de integridade</h2>
  <table>
    <tr><th>Recalculado</th><td class="{{ $recomputed === $draw->computed_number ? 'ok' : 'bad' }}">{{ $recomputed ?? 'não reproduzível' }} {{ $recomputed === $draw->computed_number ? '✔ confere' : '✘ diverge' }}</td></tr>
    <tr><th>Hash do registro</th><td><code>{{ $draw->hash }}</code> <span class="{{ $hashValid ? 'ok' : 'bad' }}">{{ $hashValid ? '✔ íntegro' : '✘ adulterado' }}</span></td></tr>
    <tr><th>Hash anterior (cadeia)</th><td><code>{{ $draw->prev_hash ?? '—' }}</code></td></tr>
  </table>

  <div class="footer">
    A prova pública deste sorteio pode ser conferida em {{ route('public.draw', [$raffle->tenant->slug, $raffle->slug]) }}
  </div>
</body>
</html>
