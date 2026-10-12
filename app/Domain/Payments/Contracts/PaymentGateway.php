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
   * Nome exibido ao organizador.
   */
  public function label(): string;

  /**
   * A conta de recebimento do organizador está pronta para este gateway.
   */
  public function isConnected(Tenant $tenant): bool;

  /**
   * Identificador da conta que recebe o split (carteira, usuário do gateway...).
   */
  public function accountId(Tenant $tenant): ?string;

  /**
   * Valida a autenticidade e normaliza o webhook; null se inválido.
   * Status null = o job consulta o status atual no gateway.
   */
  public function parseWebhook(Request $request): ?WebhookPayload;
}
