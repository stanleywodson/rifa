<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Tenants\Models\Tenant;

/**
 * Gateway em que o organizador conecta uma conta própria via OAuth (ex.: Mercado Pago marketplace).
 */
interface ConnectsAccounts
{
  public function authorizationUrl(string $state, string $redirectUri): string;

  /**
   * Troca o código de autorização pelas credenciais e grava na conta do organizador.
   */
  public function connect(Tenant $tenant, string $code, string $redirectUri): void;

  public function disconnect(Tenant $tenant): void;
}
