<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Draws\DrawService;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleStateMachine;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DrawController extends Controller
{
  public function create(Raffle $raffle)
  {
    $this->authorize('draw', $raffle);

    return view('content.organizer.raffles.draw', [
      'raffle' => $raffle->load('prize', 'draws'),
      'paid' => $raffle->tickets()->where('status', TicketStatus::Paid)->count(),
    ]);
  }

  public function store(Request $request, Raffle $raffle, DrawService $draws)
  {
    $this->authorize('draw', $raffle);
    $data = $request->validate([
      'concurso' => ['nullable', 'string', 'max:10'],
      'premio' => ['nullable', 'string', 'max:10'],
      'evidence_url' => ['nullable', 'url', 'max:500'],
      'number' => ['nullable', 'integer', 'min:0'],
      'notes' => ['nullable', 'string', 'max:1000'],
      'confirm' => ['accepted'],
    ], ['confirm.accepted' => 'Confirme que leu as regras do sorteio.'], [
      'concurso' => 'concurso',
      'premio' => '1º prêmio',
      'evidence_url' => 'link de evidência',
      'number' => 'número sorteado',
    ]);

    $draw = $draws->execute($raffle, $data);

    return redirect()->route('painel.rifas.show', [$raffle, 'aba' => 'sorteio'])
      ->with($draw->outcome->value === 'winner' ? 'success' : 'warning', $draw->outcome->label() . ' — ' . ($draw->raw_data['regra_aplicada'] ?? ''));
  }

  /**
   * Comprovante de entrega do prêmio → rifa concluída.
   */
  public function delivery(Request $request, Raffle $raffle, RaffleStateMachine $states)
  {
    $this->authorize('complete', $raffle);
    $data = $request->validate([
      'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'notes' => ['nullable', 'string', 'max:1000'],
    ], [], ['proof' => 'comprovante de entrega']);

    $states->transition($raffle, RaffleStatus::Completed, [
      'delivery_proof_path' => $request->file('proof')->store("tenants/{$raffle->tenant_id}/entregas", 'local'),
      'delivery_notes' => $data['notes'] ?? null,
      'completed_at' => now(),
    ], 'Prêmio entregue');

    return back()->with('success', 'Entrega registrada. Rifa concluída!');
  }
}
