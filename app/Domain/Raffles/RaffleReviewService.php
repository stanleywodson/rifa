<?php

namespace App\Domain\Raffles;

use App\Domain\Notifications\Mail\RaffleReviewedMail;
use App\Domain\Raffles\Enums\RaffleStatus as S;
use App\Domain\Raffles\Jobs\GenerateTicketsJob;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\KycStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fluxo de compliance: organizador envia → admin aprova / pede ajustes / reprova.
 */
class RaffleReviewService
{
  public function __construct(protected RaffleStateMachine $states) {}

  /**
   * Pendências que impedem o envio para análise.
   *
   * @return string[]
   */
  public function submissionIssues(Raffle $raffle): array
  {
    $raffle->loadMissing('prize', 'media', 'tenant.plan');
    $issues = [];

    if (!$raffle->prize) {
      $issues[] = 'Cadastre o prêmio.';
    }
    if ($raffle->media->isEmpty()) {
      $issues[] = 'Envie ao menos uma imagem do prêmio.';
    }
    if (blank($raffle->regulation)) {
      $issues[] = 'Preencha o regulamento.';
    }
    if (blank($raffle->authorization_number) || blank($raffle->authorization_doc_path)) {
      $issues[] = 'Informe o número e anexe o documento de autorização.';
    }
    if (!$raffle->draw_at || !$raffle->sales_end_at || $raffle->sales_end_at->gt($raffle->draw_at) || $raffle->sales_end_at->isPast()) {
      $issues[] = 'Defina o encerramento das vendas (futuro) antes da data do sorteio.';
    }
    if ($raffle->total_numbers > config('saas.max_numbers')) {
      $issues[] = 'Quantidade de números acima do limite da plataforma.';
    }
    if ($raffle->tenant->kyc_status === KycStatus::Pending || $raffle->tenant->kyc_status === KycStatus::Rejected) {
      $issues[] = 'Envie os documentos de verificação (KYC) do organizador.';
    }

    if (!$raffle->tenant->canReceivePayments()) {
      $issues[] = 'Configure a conta de recebimento (Configurações → Recebimentos).';
    }

    $plan = $raffle->tenant->plan;
    if ($plan) {
      if (($max = $plan->limit('max_numbers')) && $raffle->total_numbers > $max) {
        $issues[] = "Seu plano permite até {$max} números por rifa.";
      }
      $active = Raffle::query()->withoutGlobalScopes()->where('tenant_id', $raffle->tenant_id)->whereKeyNot($raffle->id)
        ->whereIn('status', [S::PendingReview, S::Published, S::SalesClosed])->count();
      if (($limit = $plan->limit('active_raffles')) && $active >= $limit) {
        $issues[] = "Seu plano permite {$limit} rifa(s) ativa(s) ao mesmo tempo.";
      }
    }

    return $issues;
  }

  public function submit(Raffle $raffle): Raffle
  {
    if ($issues = $this->submissionIssues($raffle)) {
      throw ValidationException::withMessages(['raffle' => $issues]);
    }

    return $this->states->transition($raffle, S::PendingReview, ['submitted_at' => now()]);
  }

  /**
   * Aprova: gera a semente do commit-reveal, congela as regras e cria os números.
   * A rifa fica pública quando a geração dos números termina (GenerateTicketsJob).
   */
  public function approve(Raffle $raffle, ?string $notes = null): Raffle
  {
    if ($raffle->status !== S::PendingReview) {
      throw ValidationException::withMessages(['raffle' => 'A rifa não está em análise.']);
    }
    if (!$raffle->tenant->isVerified()) {
      throw ValidationException::withMessages(['raffle' => 'Aprove a verificação (KYC) do organizador antes da rifa.']);
    }

    DB::transaction(function () use ($raffle, $notes) {
      $seed = bin2hex(random_bytes(32));
      $raffle->forceFill([
        'seed_encrypted' => $seed,
        'commit_hash' => hash('sha256', $seed),
        'approved_by' => auth()->id(),
        'approved_at' => now(),
        'review_notes' => $notes,
      ])->save();
    });

    GenerateTicketsJob::dispatch($raffle->id);
    $this->notify($raffle, 'approved');

    return $raffle;
  }

  public function requestChanges(Raffle $raffle, string $notes): Raffle
  {
    $this->states->transition($raffle, S::ChangesRequested, ['review_notes' => $notes], $notes);
    $this->notify($raffle, 'changes_requested');

    return $raffle;
  }

  public function reject(Raffle $raffle, string $notes): Raffle
  {
    $this->states->transition($raffle, S::Rejected, ['review_notes' => $notes], $notes);
    $this->notify($raffle, 'rejected');

    return $raffle;
  }

  /**
   * Volta para rascunho para edição após pedido de ajustes.
   */
  public function reopen(Raffle $raffle): Raffle
  {
    return $this->states->transition($raffle, S::Draft);
  }

  public static function uniqueSlug(int $tenantId, string $title, ?int $ignoreId = null): string
  {
    $base = Str::slug($title) ?: 'rifa';
    $base = Str::limit($base, 70, '');
    $slug = $base;
    $i = 2;
    while (Raffle::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->where('slug', $slug)
      ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))->exists()) {
      $slug = "{$base}-" . $i++;
    }

    return $slug;
  }

  protected function notify(Raffle $raffle, string $decision): void
  {
    foreach ($raffle->tenant->users as $user) {
      Mail::to($user->email)->queue(new RaffleReviewedMail($raffle, $decision));
    }
  }
}
