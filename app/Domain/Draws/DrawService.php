<?php

namespace App\Domain\Draws;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Draws\Enums\DrawOutcome;
use App\Domain\Draws\Exceptions\DrawException;
use App\Domain\Draws\Jobs\GenerateDrawMinutesJob;
use App\Domain\Draws\Models\Draw;
use App\Domain\Notifications\Mail\DrawResultMail;
use App\Domain\Notifications\Mail\WinnerMail;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Enums\UnsoldRule;
use App\Domain\Raffles\Jobs\RefundRaffleJob;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\RaffleStateMachine;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Domain\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sorteio auditável. Toda a informação necessária para recalcular o
 * resultado fica em draws.raw_data e é publicada na página de prova.
 */
class DrawService
{
  public function __construct(
    protected LoteriaFederalClient $federal,
    protected RaffleStateMachine $states,
    protected AuditLogger $audit,
  ) {}

  /**
   * @param  array{concurso?: string, premio?: string, evidence_url?: string, number?: int, notes?: string}  $input
   */
  public function execute(Raffle $raffle, array $input): Draw
  {
    if ($raffle->status !== RaffleStatus::SalesClosed) {
      throw new DrawException('O sorteio só pode ser realizado com as vendas encerradas.');
    }
    if (Order::query()->withoutGlobalScopes()->where('raffle_id', $raffle->id)->where('status', OrderStatus::AwaitingPayment)->exists()) {
      throw new DrawException('Ainda há pedidos aguardando pagamento. Aguarde a expiração das reservas.');
    }

    [$computed, $raw, $sourceRef, $seed] = match ($raffle->draw_method) {
      DrawMethod::Federal => $this->federalNumber($raffle, $input),
      DrawMethod::CommitReveal => $this->commitRevealNumber($raffle, $input),
      DrawMethod::Manual => $this->manualNumber($raffle, $input),
    };

    [$outcome, $ticket, $raw['regra_aplicada']] = $this->applyUnsoldRule($raffle, $computed);

    $draw = DB::transaction(function () use ($raffle, $computed, $raw, $sourceRef, $seed, $outcome, $ticket) {
      $prev = Draw::query()->latest('id')->lockForUpdate()->first();

      $draw = new Draw([
        'raffle_id' => $raffle->id,
        'method' => $raffle->draw_method,
        'source_ref' => $sourceRef,
        'raw_data' => $raw,
        'seed_revealed' => $seed,
        'computed_number' => $computed,
        'drawn_number' => $ticket?->number,
        'ticket_id' => $ticket?->id,
        'customer_id' => $ticket?->order?->customer_id,
        'outcome' => $outcome,
        'executed_by' => auth()->id(),
        'executed_at' => now()->startOfSecond(),
        'prev_hash' => $prev?->hash,
      ]);
      $draw->hash = $draw->computeHash();
      $draw->save();

      $this->audit->log('raffle.draw', $raffle, null, [
        'draw_id' => $draw->id,
        'outcome' => $outcome->value,
        'computed_number' => $computed,
        'drawn_number' => $draw->drawn_number,
        'hash' => $draw->hash,
      ]);

      match ($outcome) {
        DrawOutcome::Winner => $this->states->transition($raffle, RaffleStatus::Drawn),
        DrawOutcome::Void => $this->states->transition($raffle, RaffleStatus::Cancelled, [], 'Sorteio anulado pela regra de número não vendido'),
        DrawOutcome::Redraw => null,
      };

      return $draw;
    });

    GenerateDrawMinutesJob::dispatch($draw->id);

    if ($outcome === DrawOutcome::Void) {
      RefundRaffleJob::dispatch($raffle->id, 'Rifa anulada no sorteio (número sorteado não vendido).');
    }
    if ($outcome === DrawOutcome::Winner) {
      $this->notify($raffle, $draw);
    }

    return $draw;
  }

  /**
   * Recalcula o número a partir dos dados publicados (verificação independente).
   */
  public function recompute(Draw $draw): ?int
  {
    $raffle = $draw->raffle;
    $raw = $draw->raw_data;

    return match ($draw->method) {
      DrawMethod::Federal => self::modulo($raw['base'], $raffle->total_numbers) + $raffle->number_start,
      DrawMethod::CommitReveal => hash('sha256', $draw->seed_revealed) === $raw['commit_hash']
        ? self::modulo(self::seedBase($draw->seed_revealed, $raw['dado_publico']), $raffle->total_numbers) + $raffle->number_start
        : null,
      DrawMethod::Manual => (int) $raw['numero_informado'],
    };
  }

  public function verifyHash(Draw $draw): bool
  {
    return hash_equals($draw->hash, $draw->computeHash());
  }

  protected function federalNumber(Raffle $raffle, array $input): array
  {
    $result = $this->federalResult($input);
    $base = $result['premios'][0];

    return [
      self::modulo($base, $raffle->total_numbers) + $raffle->number_start,
      $result + [
        'base' => $base,
        'formula' => "(1º prêmio {$base} mod {$raffle->total_numbers}) + {$raffle->number_start}",
      ],
      'Loteria Federal — concurso ' . $result['concurso'],
      null,
    ];
  }

  protected function commitRevealNumber(Raffle $raffle, array $input): array
  {
    $seed = $raffle->seed_encrypted;
    if (!$seed || hash('sha256', $seed) !== $raffle->commit_hash) {
      throw new DrawException('Semente da rifa ausente ou inconsistente com o commit publicado.');
    }

    $result = $this->federalResult($input);
    $public = $result['premios'][0];
    $base = self::seedBase($seed, $public);

    return [
      self::modulo($base, $raffle->total_numbers) + $raffle->number_start,
      $result + [
        'commit_hash' => $raffle->commit_hash,
        'dado_publico' => $public,
        'base' => $base,
        'formula' => "(hexdec(sha256(semente|{$public})[0..14]) mod {$raffle->total_numbers}) + {$raffle->number_start}",
      ],
      'Commit-reveal + Loteria Federal — concurso ' . $result['concurso'],
      $seed,
    ];
  }

  protected function manualNumber(Raffle $raffle, array $input): array
  {
    $number = (int) ($input['number'] ?? -1);
    if ($number < $raffle->number_start || $number > $raffle->lastNumber()) {
      throw new DrawException('Número informado fora da faixa da rifa.');
    }
    if (blank($input['evidence_url'] ?? null)) {
      throw new DrawException('Informe o link da gravação do sorteio ao vivo.');
    }

    return [
      $number,
      ['numero_informado' => $number, 'evidencia' => $input['evidence_url'], 'observacoes' => $input['notes'] ?? null],
      'Sorteio manual — ' . $input['evidence_url'],
      null,
    ];
  }

  /**
   * Resultado oficial da Federal; se o portal estiver fora, aceita o 1º prêmio
   * informado manualmente com link de evidência (fica marcado na prova).
   */
  protected function federalResult(array $input): array
  {
    $concurso = trim((string) ($input['concurso'] ?? ''));
    if ($concurso === '') {
      throw new DrawException('Informe o número do concurso da Loteria Federal.');
    }

    if (filled($input['premio'] ?? null)) {
      if (blank($input['evidence_url'] ?? null)) {
        throw new DrawException('Para informar o resultado manualmente, anexe o link oficial do resultado.');
      }

      return [
        'concurso' => $concurso,
        'data' => null,
        'premios' => [only_digits($input['premio'])],
        'fonte' => $input['evidence_url'],
        'informado_manualmente' => true,
      ];
    }

    return $this->federal->result($concurso);
  }

  /**
   * @return array{0: DrawOutcome, 1: ?Ticket, 2: string}
   */
  protected function applyUnsoldRule(Raffle $raffle, int $computed): array
  {
    $paid = fn() => Ticket::query()->with('order')->where('raffle_id', $raffle->id)->where('status', TicketStatus::Paid);

    $ticket = $paid()->where('number', $computed)->first();
    if ($ticket) {
      return [DrawOutcome::Winner, $ticket, 'Número sorteado está pago'];
    }

    return match ($raffle->unsold_rule) {
      UnsoldRule::NextPaidAbove => ($next = $paid()->where('number', '>', $computed)->orderBy('number')->first()
        ?? $paid()->orderBy('number')->first())
        ? [DrawOutcome::Winner, $next, "Número {$computed} não vendido; aplicado o próximo número pago acima ({$next->number})"]
        : [DrawOutcome::Void, null, 'Nenhum número pago na rifa'],
      UnsoldRule::Redraw => [DrawOutcome::Redraw, null, "Número {$computed} não vendido; novo sorteio no próximo concurso"],
      UnsoldRule::Void => [DrawOutcome::Void, null, "Número {$computed} não vendido; rifa anulada conforme regulamento"],
    };
  }

  protected function notify(Raffle $raffle, Draw $draw): void
  {
    $draw->loadMissing('customer');
    if ($draw->customer?->email) {
      Mail::to($draw->customer->email)->queue(new WinnerMail($draw));
    }

    Order::query()->withoutGlobalScopes()->with('customer')
      ->where('raffle_id', $raffle->id)->where('status', OrderStatus::Paid)
      ->get()->pluck('customer')->unique('id')
      ->filter(fn($c) => $c->email && $c->id !== $draw->customer_id)
      ->each(fn($c) => Mail::to($c->email)->queue(new DrawResultMail($draw)));
  }

  /** Resto da divisão de um inteiro decimal arbitrariamente grande. */
  public static function modulo(string $digits, int $mod): int
  {
    $digits = ltrim(only_digits($digits), '0') ?: '0';
    $r = 0;
    foreach (str_split($digits) as $d) {
      $r = ($r * 10 + (int) $d) % $mod;
    }

    return $r;
  }

  public static function seedBase(string $seed, string $public): string
  {
    return (string) hexdec(substr(hash('sha256', $seed . '|' . $public), 0, 15));
  }
}
