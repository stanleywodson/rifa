<?php

namespace App\Domain\Tenants\Concerns;

use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tenants\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isola o model pelo organizador ativo (coluna tenant_id).
 * Sem organizador no contexto (admin da plataforma, jobs) o escopo não é aplicado.
 */
trait BelongsToTenant
{
  public static function bootBelongsToTenant(): void
  {
    static::addGlobalScope('tenant', function (Builder $builder) {
      $context = app(TenantContext::class);
      if ($context->has()) {
        $builder->where($builder->getModel()->qualifyColumn('tenant_id'), $context->id());
      }
    });

    static::creating(function ($model) {
      $context = app(TenantContext::class);
      if (!$model->tenant_id && $context->has()) {
        $model->tenant_id = $context->id();
      }
    });
  }

  public function tenant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class);
  }
}
