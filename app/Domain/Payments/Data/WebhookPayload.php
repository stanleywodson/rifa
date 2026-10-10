<?php

namespace App\Domain\Payments\Data;

use App\Domain\Payments\Enums\PaymentStatus;

/**
 * Evento de webhook já normalizado pelo gateway.
 */
final class WebhookPayload
{
  public function __construct(
    public readonly string $eventId,
    public readonly string $eventType,
    public readonly ?string $gatewayPaymentId,
    public readonly ?PaymentStatus $status,
    public readonly array $raw,
  ) {}
}
