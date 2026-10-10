<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Jobs\RefundRaffleJob;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleReviewService;
use App\Domain\Raffles\RaffleStateMachine;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RaffleReviewController extends Controller
{
  public function index(Request $request)
  {
    return view('content.admin.raffles.index', [
      'counts' => Raffle::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
      'status' => $request->query('status', RaffleStatus::PendingReview->value),
    ]);
  }

  public function data(Request $request)
  {
    $query = Raffle::query()->with(['tenant', 'media'])
      ->withCount(['tickets as sold_count' => fn($q) => $q->where('status', TicketStatus::Paid)])
      ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
      ->orderByRaw('submitted_at IS NULL')->oldest('submitted_at');

    return DataTable::make($request, $query, [0 => 'title', 2 => 'status', 3 => 'total_numbers', 4 => 'price_cents', 5 => 'submitted_at'],
      fn($q, $term) => $q->where('title', 'like', "%{$term}%")->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$term}%")),
      fn(Raffle $raffle) => [
        'raffle' => view('content.organizer.raffles._cell', ['raffle' => $raffle, 'url' => route('admin.rifas.show', $raffle)])->render(),
        'tenant' => DataTable::person($raffle->tenant->name, $raffle->tenant->kyc_status->label(), route('admin.organizadores.show', $raffle->tenant_id)),
        'status' => $raffle->status->badge() . ($raffle->approved_at && $raffle->status === RaffleStatus::PendingReview ? ' <span class="badge bg-label-info">Gerando números</span>' : ''),
        'numbers' => number_format($raffle->total_numbers, 0, ',', '.'),
        'price' => $raffle->priceFormatted(),
        'submitted' => DataTable::date($raffle->submitted_at),
        'actions' => DataTable::actions(['Analisar' => route('admin.rifas.show', $raffle)]),
      ]);
  }

  public function show(Raffle $raffle, RaffleReviewService $review)
  {
    $raffle->load(['tenant.kycDocuments', 'tenant.plan', 'prize', 'media', 'draws', 'approver']);

    return view('content.admin.raffles.show', [
      'raffle' => $raffle,
      'issues' => $review->submissionIssues($raffle),
      'logs' => AuditLog::query()->with('user')->where('subject_type', 'Raffle')->where('subject_id', $raffle->id)->latest('id')->get(),
      'sold' => $raffle->soldCount(),
    ]);
  }

  public function authorization(Raffle $raffle)
  {
    abort_unless($raffle->authorization_doc_path && Storage::disk('local')->exists($raffle->authorization_doc_path), 404);

    return Storage::disk('local')->response($raffle->authorization_doc_path);
  }

  public function approve(Request $request, Raffle $raffle, RaffleReviewService $review)
  {
    $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);
    $review->approve($raffle, $data['notes'] ?? null);

    return redirect()->route('admin.rifas.show', $raffle)->with('success', 'Rifa aprovada. Os números estão sendo gerados e a rifa será publicada em seguida.');
  }

  public function requestChanges(Request $request, Raffle $raffle, RaffleReviewService $review)
  {
    $data = $request->validate(['notes' => ['required', 'string', 'min:10', 'max:2000']], [], ['notes' => 'ajustes solicitados']);
    $review->requestChanges($raffle, $data['notes']);

    return redirect()->route('admin.rifas.index')->with('success', 'Ajustes solicitados ao organizador.');
  }

  public function reject(Request $request, Raffle $raffle, RaffleReviewService $review)
  {
    $data = $request->validate(['notes' => ['required', 'string', 'min:10', 'max:2000']], [], ['notes' => 'motivo']);
    $review->reject($raffle, $data['notes']);

    return redirect()->route('admin.rifas.index')->with('success', 'Rifa reprovada.');
  }

  /**
   * Moderação: suspende uma rifa publicada (denúncia, fraude) com reembolso total.
   */
  public function cancel(Request $request, Raffle $raffle, RaffleStateMachine $states)
  {
    $data = $request->validate(['notes' => ['required', 'string', 'min:10', 'max:2000']], [], ['notes' => 'motivo']);
    $hadSales = in_array($raffle->status, [RaffleStatus::Published, RaffleStatus::SalesClosed], true);
    $states->transition($raffle, RaffleStatus::Cancelled, ['review_notes' => $data['notes']], $data['notes']);
    if ($hadSales) {
      RefundRaffleJob::dispatch($raffle->id, 'Rifa cancelada pela plataforma: ' . $data['notes']);
    }

    return back()->with('success', 'Rifa cancelada' . ($hadSales ? ' e reembolsos iniciados.' : '.'));
  }
}
