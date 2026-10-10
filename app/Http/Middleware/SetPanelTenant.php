<?php

namespace App\Http\Middleware;

use App\Domain\Tenants\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o organizador ativo do painel a partir da sessão, validando o
 * vínculo do usuário. Roda antes do route model binding (ver bootstrap/app.php),
 * de modo que {raffle}, {order} etc. já sejam buscados com o escopo do tenant.
 */
class SetPanelTenant
{
  public function __construct(protected TenantContext $context) {}

  public function handle(Request $request, Closure $next): Response
  {
    $user = $request->user();
    $tenants = $user->tenants;

    if ($tenants->isEmpty()) {
      return $user->isPlatformAdmin()
        ? redirect()->route('admin.dashboard')
        : abort(403, 'Sua conta não está vinculada a nenhum organizador.');
    }

    $tenant = $tenants->firstWhere('id', $request->session()->get('tenant_id')) ?? $tenants->first();
    $request->session()->put('tenant_id', $tenant->id);

    if (!$tenant->isActive() && !$request->isMethodSafe()) {
      abort(403, 'Organizador suspenso: o painel está em modo somente leitura.');
    }

    $this->context->set($tenant);
    View::share('currentTenant', $tenant);

    return $next($request);
  }
}
