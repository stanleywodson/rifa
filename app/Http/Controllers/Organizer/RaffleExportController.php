<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RaffleExportController extends Controller
{
  /**
   * Compradores e números pagos (CPF mascarado — LGPD, minimização).
   */
  public function csv(Raffle $raffle): StreamedResponse
  {
    $this->authorize('export', $raffle);

    return response()->streamDownload(function () use ($raffle) {
      $out = fopen('php://output', 'w');
      fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer UTF-8
      fputcsv($out, ['Pedido', 'Data pagamento', 'Comprador', 'CPF', 'Telefone', 'E-mail', 'Qtd', 'Números', 'Total (R$)'], ';');

      $raffle->orders()->with(['customer', 'items'])->where('status', OrderStatus::Paid)->orderBy('paid_at')
        ->chunk(500, function ($orders) use ($out, $raffle) {
          foreach ($orders as $order) {
            fputcsv($out, [
              $order->code,
              $order->paid_at?->format('d/m/Y H:i'),
              $order->customer->name,
              $order->customer->maskedCpf(),
              mask_phone($order->customer->phone),
              $order->customer->email,
              $order->quantity,
              $order->items->map(fn($i) => $raffle->formatNumber($i->number))->implode(' '),
              number_format($order->total_cents / 100, 2, ',', ''),
            ], ';');
          }
        });
      fclose($out);
    }, "rifa-{$raffle->slug}-vendas.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
  }

  /**
   * Relatório final / prestação de contas.
   */
  public function pdf(Raffle $raffle)
  {
    $this->authorize('export', $raffle);
    $raffle->load(['tenant', 'prize', 'draws.ticket', 'draws.customer']);

    $paid = $raffle->orders()->where('status', OrderStatus::Paid);
    $gross = (int) (clone $paid)->sum('total_cents');
    $fee = $raffle->tenant->feePercent();

    $pdf = Pdf::loadView('pdf.raffle-report', [
      'raffle' => $raffle,
      'gross' => $gross,
      'feePercent' => $fee,
      'feeAmount' => (int) round($gross * $fee / 100),
      'paidOrders' => (clone $paid)->count(),
      'soldNumbers' => $raffle->tickets()->where('status', TicketStatus::Paid)->count(),
      'refunded' => $raffle->orders()->where('status', OrderStatus::Refunded)->sum('total_cents'),
      'buyers' => (clone $paid)->distinct('customer_id')->count('customer_id'),
    ]);

    return $pdf->download("relatorio-rifa-{$raffle->slug}.pdf");
  }

  public function authorization(Raffle $raffle)
  {
    $this->authorize('view', $raffle);
    abort_unless($raffle->authorization_doc_path && Storage::disk('local')->exists($raffle->authorization_doc_path), 404);

    return Storage::disk('local')->response($raffle->authorization_doc_path);
  }
}
