<?php

namespace App\Domain\Draws\Jobs;

use App\Domain\Draws\DrawService;
use App\Domain\Draws\Models\Draw;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Gera a ata do sorteio em PDF (armazenada em disco privado).
 */
class GenerateDrawMinutesJob implements ShouldQueue
{
  use Queueable;

  public function __construct(public int $drawId) {}

  public function handle(DrawService $draws): void
  {
    $draw = Draw::with(['raffle' => fn($q) => $q->withoutGlobalScopes(), 'raffle.tenant', 'raffle.prize', 'ticket', 'customer', 'executor'])->findOrFail($this->drawId);

    $pdf = Pdf::loadView('pdf.draw-minutes', [
      'draw' => $draw,
      'raffle' => $draw->raffle,
      'recomputed' => $draws->recompute($draw),
      'hashValid' => $draws->verifyHash($draw),
    ])->setPaper('a4');

    $path = "atas/sorteio-{$draw->id}.pdf";
    Storage::disk('local')->put($path, $pdf->output());
    $draw->forceFill(['minutes_pdf_path' => $path])->save();
  }
}
