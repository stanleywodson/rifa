<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Relatório — {{ $raffle->title }}</title>
  @include('pdf._style')
</head>
<body>
  <div class="header">
    <h1>Relatório da Rifa</h1>
    <div class="muted">{{ $raffle->tenant->name }} · Gerado em {{ now()->format('d/m/Y H:i') }}</div>
  </div>

  <h2>Dados gerais</h2>
  <table>
    <tr><th>Título</th><td>{{ $raffle->title }}</td></tr>
    <tr><th>Status</th><td>{{ $raffle->status->label() }}</td></tr>
    <tr><th>Prêmio</th><td>{{ $raffle->prize?->title }} @if($raffle->prize?->estimated_value_cents) ({{ money_br($raffle->prize->estimated_value_cents) }}) @endif</td></tr>
    <tr><th>Autorização</th><td>{{ $raffle->authorization_number ?: '—' }}</td></tr>
    <tr><th>Publicada em</th><td>{{ $raffle->published_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    <tr><th>Vendas encerradas em</th><td>{{ $raffle->sales_closed_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
  </table>

  <h2>Vendas</h2>
  <table>
    <tr><th>Números vendidos</th><td>{{ $soldNumbers }} de {{ $raffle->total_numbers }}</td></tr>
    <tr><th>Pedidos pagos</th><td>{{ $paidOrders }} ({{ $buyers }} compradores)</td></tr>
    <tr><th>Arrecadação bruta</th><td>{{ money_br($gross) }}</td></tr>
    <tr><th>Taxa da plataforma ({{ $feePercent }}%)</th><td>{{ money_br($feeAmount) }}</td></tr>
    <tr><th>Líquido do organizador</th><td><strong>{{ money_br($gross - $feeAmount) }}</strong></td></tr>
    <tr><th>Reembolsado</th><td>{{ money_br((int) $refunded) }}</td></tr>
  </table>

  <h2>Sorteio</h2>
  @forelse ($raffle->draws as $draw)
    <table>
      <tr><th>Data</th><td>{{ $draw->executed_at->format('d/m/Y H:i') }} — {{ $draw->method->label() }}</td></tr>
      <tr><th>Resultado</th><td>{{ $draw->outcome->label() }} @if($draw->drawn_number !== null) — número {{ $raffle->formatNumber($draw->drawn_number) }} ({{ $draw->customer?->name }}) @endif</td></tr>
      <tr><th>Hash</th><td><code>{{ $draw->hash }}</code></td></tr>
    </table>
  @empty
    <p class="muted">Sorteio ainda não realizado.</p>
  @endforelse

  @if ($raffle->completed_at)
    <h2>Entrega do prêmio</h2>
    <p>Prêmio entregue em {{ $raffle->completed_at->format('d/m/Y') }}. {{ $raffle->delivery_notes }}</p>
  @endif
</body>
</html>
