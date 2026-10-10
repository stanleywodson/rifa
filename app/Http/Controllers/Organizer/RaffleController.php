<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Enums\UnsoldRule;
use App\Domain\Raffles\Jobs\RefundRaffleJob;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleReviewService;
use App\Domain\Raffles\RaffleStateMachine;
use App\Domain\Tenants\TenantContext;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RaffleRequest;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaffleController extends Controller
{
  public function __construct(protected TenantContext $context, protected AuditLogger $audit) {}

  public function index()
  {
    $counts = Raffle::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

    return view('content.organizer.raffles.index', [
      'counts' => $counts,
      'canCreate' => request()->user()->can('create', Raffle::class),
    ]);
  }

  public function data(Request $request)
  {
    $query = Raffle::query()->with('media')
      ->withCount(['tickets as sold_count' => fn($q) => $q->where('status', TicketStatus::Paid)])
      ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
      ->latest();

    return DataTable::make($request, $query, [0 => 'title', 1 => 'status', 3 => 'price_cents', 4 => 'draw_at'],
      fn($q, $term) => $q->where('title', 'like', "%{$term}%"),
      fn(Raffle $raffle) => [
        'raffle' => view('content.organizer.raffles._cell', ['raffle' => $raffle])->render(),
        'status' => $raffle->status->badge(),
        'progress' => view('content.organizer.raffles._progress', ['sold' => $raffle->sold_count, 'total' => $raffle->total_numbers])->render(),
        'price' => $raffle->priceFormatted(),
        'draw_at' => DataTable::date($raffle->draw_at),
        'actions' => DataTable::actions(array_filter([
          'Ver detalhes' => route('painel.rifas.show', $raffle),
          'Editar' => $raffle->isEditable() ? route('painel.rifas.edit', $raffle) : null,
          'Página pública' => $raffle->isPubliclyVisible() ? $raffle->publicUrl() : null,
        ])),
      ]);
  }

  public function create()
  {
    $this->authorize('create', Raffle::class);

    return view('content.organizer.raffles.form', $this->formData(new Raffle([
      'number_start' => 0,
      'total_numbers' => 100,
      'min_numbers_per_order' => 1,
      'draw_method' => DrawMethod::Federal,
      'unsold_rule' => UnsoldRule::NextPaidAbove,
    ])));
  }

  public function store(RaffleRequest $request)
  {
    $this->authorize('create', Raffle::class);
    $tenant = $this->context->get();

    $raffle = DB::transaction(function () use ($request, $tenant) {
      $raffle = new Raffle($request->raffleAttributes());
      $raffle->tenant_id = $tenant->id;
      $raffle->created_by = $request->user()->id;
      $raffle->slug = RaffleReviewService::uniqueSlug($tenant->id, $raffle->title);
      $raffle->status = RaffleStatus::Draft;
      $raffle->save();

      $this->savePrizeAndFiles($request, $raffle);
      $this->audit->log('raffle.created', $raffle, null, ['title' => $raffle->title]);

      return $raffle;
    });

    return redirect()->route('painel.rifas.show', $raffle)->with('success', 'Rifa salva como rascunho. Revise e envie para análise.');
  }

  public function show(Raffle $raffle, RaffleReviewService $review)
  {
    $this->authorize('view', $raffle);
    $raffle->load(['prize', 'media', 'draws.ticket', 'draws.customer', 'approver']);

    $orders = $raffle->orders()->selectRaw('status, count(*) as total, sum(total_cents) as amount')->groupBy('status')->get()->keyBy(fn($r) => $r->status->value);
    $tickets = $raffle->tickets()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

    return view('content.organizer.raffles.show', [
      'raffle' => $raffle,
      'orders' => $orders,
      'tickets' => $tickets,
      'revenue' => (int) ($orders[OrderStatus::Paid->value]->amount ?? 0),
      'fee' => $raffle->tenant->feePercent(),
      'issues' => $raffle->isEditable() ? $review->submissionIssues($raffle) : [],
      'logs' => AuditLog::query()->with('user')->where('subject_type', 'Raffle')->where('subject_id', $raffle->id)->latest('id')->limit(50)->get(),
    ]);
  }

  public function edit(Raffle $raffle)
  {
    $this->authorize('update', $raffle);

    return view('content.organizer.raffles.form', $this->formData($raffle->load('prize', 'media')));
  }

  public function update(RaffleRequest $request, Raffle $raffle)
  {
    $this->authorize('update', $raffle);

    DB::transaction(function () use ($request, $raffle) {
      $original = $raffle->getAttributes();
      $raffle->fill($request->raffleAttributes());
      if ($raffle->isDirty('title')) {
        $raffle->slug = RaffleReviewService::uniqueSlug($raffle->tenant_id, $raffle->title, $raffle->id);
      }
      $raffle->save();
      $this->savePrizeAndFiles($request, $raffle);
      $this->audit->logChanges('raffle.updated', $raffle, $original);
    });

    return redirect()->route('painel.rifas.show', $raffle)->with('success', 'Alterações salvas.');
  }

  public function destroy(Raffle $raffle)
  {
    $this->authorize('delete', $raffle);
    $this->audit->log('raffle.deleted', $raffle, ['title' => $raffle->title]);
    $raffle->delete();

    return redirect()->route('painel.rifas.index')->with('success', 'Rascunho excluído.');
  }

  public function submit(Raffle $raffle, RaffleReviewService $review)
  {
    $this->authorize('submit', $raffle);
    $review->submit($raffle);

    return back()->with('success', 'Rifa enviada para análise. Você será avisado por e-mail.');
  }

  public function reopen(Raffle $raffle, RaffleReviewService $review)
  {
    $this->authorize('update', $raffle);
    $review->reopen($raffle);

    return redirect()->route('painel.rifas.edit', $raffle);
  }

  public function cancel(Request $request, Raffle $raffle, RaffleStateMachine $states)
  {
    $this->authorize('cancel', $raffle);
    $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']], [], ['reason' => 'motivo']);

    $hadSales = in_array($raffle->status, [RaffleStatus::Published, RaffleStatus::SalesClosed], true);
    $states->transition($raffle, RaffleStatus::Cancelled, [], $data['reason']);
    if ($hadSales) {
      RefundRaffleJob::dispatch($raffle->id, 'Rifa cancelada pelo organizador: ' . $data['reason']);
    }

    return back()->with('success', $hadSales ? 'Rifa cancelada. Os pagamentos serão reembolsados integralmente.' : 'Rifa cancelada.');
  }

  protected function savePrizeAndFiles(RaffleRequest $request, Raffle $raffle): void
  {
    $raffle->prizes()->updateOrCreate(['position' => 1], [
      'title' => $request->input('prize_title'),
      'description' => $request->input('prize_description'),
      'estimated_value_cents' => $request->filled('prize_value') ? (int) round((float) $request->input('prize_value') * 100) : null,
    ]);

    if ($request->hasFile('authorization_doc')) {
      $raffle->forceFill([
        'authorization_doc_path' => $request->file('authorization_doc')->store("tenants/{$raffle->tenant_id}/autorizacoes", 'local'),
      ])->save();
    }

    $sort = (int) $raffle->media()->max('sort');
    foreach ($request->file('images', []) as $image) {
      $raffle->media()->create([
        'path' => $image->store("raffles/{$raffle->id}", 'public'),
        'sort' => ++$sort,
      ]);
    }
  }

  protected function formData(Raffle $raffle): array
  {
    return [
      'raffle' => $raffle,
      'drawMethods' => DrawMethod::cases(),
      'unsoldRules' => UnsoldRule::cases(),
      'maxNumbers' => min(config('saas.max_numbers'), $this->context->get()?->plan?->limit('max_numbers') ?? PHP_INT_MAX),
    ];
  }
}
