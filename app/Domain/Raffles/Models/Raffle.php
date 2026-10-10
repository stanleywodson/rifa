<?php

namespace App\Domain\Raffles\Models;

use App\Domain\Draws\Models\Draw;
use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Enums\UnsoldRule;
use App\Domain\Tenants\Concerns\BelongsToTenant;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use Database\Factories\RaffleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use LogicException;

#[UseFactory(RaffleFactory::class)]
class Raffle extends Model
{
  use BelongsToTenant, HasFactory;

  /** Campos congelados após a publicação (regras do sorteio e da venda). */
  public const LOCKED_AFTER_PUBLISH = [
    'total_numbers', 'number_start', 'price_cents', 'draw_method', 'unsold_rule',
    'draw_rules', 'regulation', 'authorization_number', 'commit_hash', 'seed_encrypted',
  ];

  protected $fillable = [
    'tenant_id', 'created_by', 'title', 'slug', 'summary', 'description', 'total_numbers', 'number_start',
    'price_cents', 'min_numbers_per_order', 'sales_end_at', 'draw_at', 'draw_method', 'unsold_rule',
    'draw_rules', 'regulation', 'authorization_number', 'authorization_doc_path',
  ];

  protected $hidden = ['seed_encrypted'];

  protected function casts(): array
  {
    return [
      'status' => RaffleStatus::class,
      'draw_method' => DrawMethod::class,
      'unsold_rule' => UnsoldRule::class,
      'draw_rules' => 'array',
      'seed_encrypted' => 'encrypted',
      'sales_end_at' => 'datetime',
      'draw_at' => 'datetime',
      'approved_at' => 'datetime',
      'submitted_at' => 'datetime',
      'tickets_generated_at' => 'datetime',
      'published_at' => 'datetime',
      'sales_closed_at' => 'datetime',
      'completed_at' => 'datetime',
    ];
  }

  protected static function booted(): void
  {
    static::updating(function (Raffle $raffle) {
      $original = RaffleStatus::tryFrom((string) $raffle->getRawOriginal('status'));
      $frozen = $original && !in_array($original, [RaffleStatus::Draft, RaffleStatus::ChangesRequested, RaffleStatus::PendingReview], true);
      // commit_hash/seed são gravados uma única vez, na aprovação
      $dirty = array_intersect(array_keys($raffle->getDirty()), self::LOCKED_AFTER_PUBLISH);
      if ($frozen && $dirty) {
        throw new LogicException('Regras da rifa não podem ser alteradas após a publicação: ' . implode(', ', $dirty));
      }
    });
  }

  public function approver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approved_by');
  }

  public function prizes(): HasMany
  {
    return $this->hasMany(RafflePrize::class)->orderBy('position');
  }

  public function prize(): HasOne
  {
    return $this->hasOne(RafflePrize::class)->where('position', 1);
  }

  public function media(): HasMany
  {
    return $this->hasMany(RaffleMedia::class)->orderBy('sort');
  }

  public function tickets(): HasMany
  {
    return $this->hasMany(Ticket::class);
  }

  public function orders(): HasMany
  {
    return $this->hasMany(Order::class);
  }

  public function draws(): HasMany
  {
    return $this->hasMany(Draw::class)->latest('id');
  }

  public function lastDraw(): HasOne
  {
    return $this->hasOne(Draw::class)->latestOfMany();
  }

  public function isEditable(): bool
  {
    return in_array($this->status, [RaffleStatus::Draft, RaffleStatus::ChangesRequested], true);
  }

  public function isOnSale(): bool
  {
    return $this->status === RaffleStatus::Published
      && $this->tickets_generated_at !== null
      && ($this->sales_end_at === null || $this->sales_end_at->isFuture())
      && config('saas.sales_enabled')
      && $this->tenant?->canReceivePayments();
  }

  /**
   * Publicada e dentro do prazo, mas bloqueada porque o organizador não tem
   * conta de recebimento válida no gateway atual.
   */
  public function isBlockedByPayments(): bool
  {
    return $this->status === RaffleStatus::Published
      && $this->tickets_generated_at !== null
      && ($this->sales_end_at === null || $this->sales_end_at->isFuture())
      && !$this->tenant?->canReceivePayments();
  }

  public function isPubliclyVisible(): bool
  {
    return in_array($this->status, [RaffleStatus::Published, RaffleStatus::SalesClosed, RaffleStatus::Drawn, RaffleStatus::Completed], true)
      && $this->tenant?->isActive();
  }

  public function lastNumber(): int
  {
    return $this->number_start + $this->total_numbers - 1;
  }

  public function numberDigits(): int
  {
    return strlen((string) $this->lastNumber());
  }

  public function formatNumber(int $number): string
  {
    return str_pad((string) $number, $this->numberDigits(), '0', STR_PAD_LEFT);
  }

  public function priceFormatted(): string
  {
    return money_br($this->price_cents);
  }

  public function soldCount(): int
  {
    return $this->tickets()->where('status', TicketStatus::Paid)->count();
  }

  public function soldPercent(): float
  {
    return $this->total_numbers ? round($this->soldCount() * 100 / $this->total_numbers, 1) : 0;
  }

  public function coverUrl(): ?string
  {
    $media = $this->relationLoaded('media') ? $this->media->first() : $this->media()->first();

    return $media ? Storage::disk('public')->url($media->path) : null;
  }

  public function publicUrl(): string
  {
    return route('public.raffle', [$this->tenant->slug, $this->slug]);
  }
}
