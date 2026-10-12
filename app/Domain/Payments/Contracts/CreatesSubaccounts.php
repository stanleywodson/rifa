<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Tenants\Models\Tenant;

/**
 * Gateway em que a plataforma cria a conta de recebimento do organizador (ex.: subconta Asaas).
 */
interface CreatesSubaccounts
{
  /**
   * @return array{account_id: string, wallet_id: string, api_key: ?string}
   */
  public function createSubaccount(Tenant $tenant, array $data): array;
}
