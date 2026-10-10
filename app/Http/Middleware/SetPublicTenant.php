<?php

namespace App\Http\Middleware;

use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tenants\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Páginas públicas /{tenant}/...: resolve o organizador pelo slug antes do
 * binding da rifa (que passa a ser escopada pelo tenant).
 */
class SetPublicTenant
{
  public function __construct(protected TenantContext $context) {}

  public function handle(Request $request, Closure $next): Response
  {
    $param = $request->route('tenant');
    $tenant = $param instanceof Tenant ? $param : Tenant::query()->where('slug', (string) $param)->first();

    abort_if(!$tenant || !$tenant->isActive(), 404);

    $request->route()->setParameter('tenant', $tenant);
    $this->context->set($tenant);

    return $next($request);
  }
}
