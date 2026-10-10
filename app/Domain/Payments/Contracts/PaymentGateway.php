<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Data\PixCharge;
use App\Domain\Payments\Data\WebhookPayload;
use App\Domain\Payments\Models\Payment;
use App\Domain\Tenants\Models\Tenant;
use Illuminate\Http\Request;

interface PaymentGateway
{
  public function name(): string;

  /**
   * Cria a cobrança Pix do pedido, com split para a carteira do organizador.
   */
  public function createPixCharge(Order $order, float $platformFeePercent): PixCharge;

  /**
   * Consulta o status atual da cobrança (conciliação).
   */
  public function getCharge(Payment $payment): PixCharge;

  /**
   * Cancela cobrança ainda não paga (reserva expirada).
   */
  public function cancelCharge(Payment $payment): void;

  public function refund(Payment $payment, string $reason): array;

  /**
   * Cria a subconta do organizador; retorna [account_id, wallet_id, api_key].
   *
   * @return array{account_id: string, wallet_id: string, api_key: ?string}
   */
  public function createSubaccount(Tenant $tenant, array $data): array;

  /**
   * Valida a autenticidade e normaliza o webhook; null se inválido.
   */
  public function parseWebhook(Request $request): ?WebhookPayload;
}
