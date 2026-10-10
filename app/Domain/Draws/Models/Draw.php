<?php

namespace App\Domain\Draws\Models;

use App\Domain\Draws\Enums\DrawOutcome;
use App\Domain\Orders\Models\Customer;
use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Draw extends Model
{
  protected $fillable = [
    'raffle_id', 'method', 'source_ref', 'raw_data', 'seed_revealed', 'computed_number', 'drawn_number',
    'ticket_id', 'customer_id', 'outcome', 'executed_by', 'executed_at', 'prev_hash', 'hash',
  ];

  protected function casts(): array
  {
    return [
      'method' => DrawMethod::class,
      'outcome' => DrawOutcome::class,
      'raw_data' => 'array',
      'executed_at' => 'datetime',
    ];
  }

  protected static function booted(): void
  {
    // Apenas o caminho da ata pode ser preenchido depois do registro.
    static::updating(function (Draw $draw) {
      if (array_diff(array_keys($draw->getDirty()), ['minutes_pdf_path', 'updated_at'])) {
        throw new LogicException('O registro do sorteio é imutável.');
      }
    });
    static::deleting(fn() => throw new LogicException('O registro do sorteio é imutável.'));
  }

  public function raffle(): BelongsTo
  {
    return $this->belongsTo(Raffle::class);
  }

  public function ticket(): BelongsTo
  {
    return $this->belongsTo(Ticket::class);
  }

  public function customer(): BelongsTo
  {
    return $this->belongsTo(Customer::class);
  }

  public function executor(): BelongsTo
  {
    return $this->belongsTo(User::class, 'executed_by');
  }

  public function hashPayload(): string
  {
    return json_encode([
      'raffle_id' => (int) $this->raffle_id,
      'method' => $this->method->value,
      'source_ref' => $this->source_ref,
      'raw_data' => canonical_value($this->raw_data ?? []),
      'seed_revealed' => $this->seed_revealed,
      'computed_number' => $this->computed_number === null ? null : (int) $this->computed_number,
      'drawn_number' => $this->drawn_number === null ? null : (int) $this->drawn_number,
      'ticket_id' => $this->ticket_id === null ? null : (int) $this->ticket_id,
      'outcome' => $this->outcome->value,
      'executed_at' => $this->executed_at->format('Y-m-d H:i:s'),
      'prev_hash' => $this->prev_hash,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  public function computeHash(): string
  {
    return hash('sha256', $this->hashPayload());
  }
}
