<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\AsaasGateway;
use App\Domain\Payments\Gateways\FakeGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
  /** @var array<string, PaymentGateway> */
  protected array $resolved = [];

  public function default(): PaymentGateway
  {
    return $this->driver(config('saas.payment_gateway'));
  }

  public function driver(string $name): PaymentGateway
  {
    return $this->resolved[$name] ??= match ($name) {
      'asaas' => new AsaasGateway(config('saas.asaas')),
      'fake' => new FakeGateway,
      default => throw new InvalidArgumentException("Gateway de pagamento desconhecido: {$name}"),
    };
  }
}
