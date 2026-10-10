<?php

namespace App\Domain\Compliance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Trilha de auditoria imutável (somente inserção, hash encadeado).
 */
class AuditLog extends Model
{
  public const UPDATED_AT = null;

  protected $guarded = [];

  protected function casts(): array
  {
    return [
      'before' => 'array',
      'after' => 'array',
      'created_at' => 'datetime',
    ];
  }

  protected static function booted(): void
  {
    static::updating(fn() => throw new LogicException('Registros de auditoria não podem ser alterados.'));
    static::deleting(fn() => throw new LogicException('Registros de auditoria não podem ser excluídos.'));
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Conteúdo canônico usado no hash do registro.
   */
  public function hashPayload(): string
  {
    return json_encode([
      'tenant_id' => $this->tenant_id === null ? null : (int) $this->tenant_id,
      'user_id' => $this->user_id === null ? null : (int) $this->user_id,
      'action' => $this->action,
      'subject_type' => $this->subject_type,
      'subject_id' => $this->subject_id === null ? null : (int) $this->subject_id,
      'before' => canonical_value($this->before),
      'after' => canonical_value($this->after),
      'ip' => $this->ip,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'prev_hash' => $this->prev_hash,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  public function computeHash(): string
  {
    return hash('sha256', $this->hashPayload());
  }
}
