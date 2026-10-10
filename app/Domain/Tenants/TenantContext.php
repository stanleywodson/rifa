<?php

namespace App\Domain\Tenants;

use App\Domain\Tenants\Models\Tenant;

/**
 * Organizador ativo na requisição atual. Resolvido no servidor
 * (sessão no painel, slug da URL nas páginas públicas) — nunca a partir
 * de tenant_id enviado pelo navegador.
 */
class TenantContext
{
  protected ?Tenant $tenant = null;

  public function set(?Tenant $tenant): void
  {
    $this->tenant = $tenant;
  }

  public function get(): ?Tenant
  {
    return $this->tenant;
  }

  public function id(): ?int
  {
    return $this->tenant?->id;
  }

  public function has(): bool
  {
    return $this->tenant !== null;
  }

  public function forget(): void
  {
    $this->tenant = null;
  }
}
