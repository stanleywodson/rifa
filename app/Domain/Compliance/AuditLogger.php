<?php

namespace App\Domain\Compliance;

use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Tenants\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
  /** Campos nunca gravados na trilha. */
  protected const SENSITIVE = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'asaas_api_key', 'seed_encrypted'];

  public function __construct(protected TenantContext $context) {}

  public function log(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null, ?int $tenantId = null): AuditLog
  {
    $request = app()->runningInConsole() && !app()->runningUnitTests() ? null : request();

    return DB::transaction(function () use ($action, $subject, $before, $after, $tenantId, $request) {
      // Serializa a cadeia: trava o último registro antes de encadear.
      $prev = AuditLog::query()->latest('id')->lockForUpdate()->first();

      $log = new AuditLog([
        'tenant_id' => $tenantId ?? $subject?->getAttribute('tenant_id') ?? $this->context->id(),
        'user_id' => auth()->id(),
        'action' => $action,
        'subject_type' => $subject ? class_basename($subject) : null,
        'subject_id' => $subject?->getKey(),
        'before' => $before ? Arr::except($before, self::SENSITIVE) : null,
        'after' => $after ? Arr::except($after, self::SENSITIVE) : null,
        'ip' => $request?->ip(),
        'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        'prev_hash' => $prev?->hash,
      ]);
      $log->created_at = now();
      $log->hash = $log->computeHash();
      $log->save();

      return $log;
    });
  }

  /**
   * Alterações de um model (somente atributos modificados).
   */
  public function logChanges(string $action, Model $subject, array $original): AuditLog
  {
    $changed = array_keys($subject->getChanges());
    $before = Arr::only($original, $changed);
    $after = Arr::only($subject->getAttributes(), $changed);

    return $this->log($action, $subject, $before, $after);
  }

  /**
   * Recalcula a cadeia; retorna o id do primeiro registro adulterado ou null.
   */
  public function verifyChain(): ?int
  {
    $prevHash = null;
    $broken = null;

    AuditLog::query()->orderBy('id')->chunk(1000, function ($logs) use (&$prevHash, &$broken) {
      foreach ($logs as $log) {
        if ($log->prev_hash !== $prevHash || !hash_equals($log->hash, $log->computeHash())) {
          $broken = $log->id;

          return false;
        }
        $prevHash = $log->hash;
      }
    });

    return $broken;
  }
}
