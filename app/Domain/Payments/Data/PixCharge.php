<?php

namespace App\Domain\Payments\Data;

use App\Domain\Payments\Enums\PaymentStatus;

final class PixCharge
{
  public function __construct(
    public readonly string $gatewayId,
    public readonly PaymentStatus $status,
    public readonly ?string $qrImageBase64 = null,
    public readonly ?string $copyPaste = null,
    public readonly array $raw = [],
  ) {}
}
