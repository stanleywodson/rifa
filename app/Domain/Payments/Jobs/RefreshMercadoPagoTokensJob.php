<?php

namespace App\Domain\Payments\Jobs;

use App\Domain\Payments\Gateways\MercadoPagoGateway;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Tenants\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Renova os tokens OAuth do Mercado Pago que vencem nos próximos 30 dias, para
 * que organizadores sem vendas recentes não percam a conexão (validade de 180 dias).
 */
class RefreshMercadoPagoTokensJob implements ShouldQueue, ShouldBeUnique
{
  use Queueable;

  public function handle(PaymentGatewayManager $gateways): void
  {
    /** @var MercadoPagoGateway $gateway */
    $gateway = $gateways->driver('mercadopago');

    Tenant::query()
      ->where('payment_gateway', 'mercadopago')
      ->whereNotNull('mp_refresh_token')
      ->where('mp_token_expires_at', '<=', now()->addDays(30))
      ->chunkById(50, function ($tenants) use ($gateway) {
        foreach ($tenants as $tenant) {
          try {
            $gateway->refreshToken($tenant);
          } catch (Throwable $e) {
            Log::warning('Falha ao renovar token Mercado Pago', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
          }
        }
      });
  }
}
