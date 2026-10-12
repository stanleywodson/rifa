<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\AsaasGateway;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Payments\Gateways\MercadoPagoGateway;
use App\Domain\Tenants\Models\Tenant;
use InvalidArgumentException;

class PaymentGatewayManager
{
  public const DRIVERS = ['fake', 'asaas', 'mercadopago'];

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
      'mercadopago' => new MercadoPagoGateway(config('saas.mercadopago')),
      'fake' => new FakeGateway,
      default => throw new InvalidArgumentException("Gateway de pagamento desconhecido: {$name}"),
    };
  }

  /**
   * Gateways habilitados na plataforma (o padrão + PAYMENT_GATEWAYS).
   *
   * @return string[]
   */
  public function enabled(): array
  {
    $names = array_merge([config('saas.payment_gateway')], (array) config('saas.payment_gateways', []));

    return array_values(array_intersect(self::DRIVERS, array_unique($names)));
  }

  public function isEnabled(string $name): bool
  {
    return in_array($name, $this->enabled(), true);
  }

  /**
   * Gateways que o organizador pode escolher para conectar a conta.
   *
   * @return array<string, PaymentGateway>
   */
  public function available(): array
  {
    return collect($this->enabled())->mapWithKeys(fn($name) => [$name => $this->driver($name)])->all();
  }

  /**
   * Gateway em que o organizador tem conta de recebimento válida.
   */
  public function connected(Tenant $tenant): ?PaymentGateway
  {
    $name = $tenant->payment_gateway;
    if (!$name || !$this->isEnabled($name)) {
      return null;
    }
    $driver = $this->driver($name);

    return $driver->isConnected($tenant) ? $driver : null;
  }

  /**
   * Gateway usado nas cobranças do organizador. No modo de testes (padrão "fake")
   * todos recebem pelo gateway simulado, mesmo sem conta conectada.
   */
  public function forTenant(Tenant $tenant): ?PaymentGateway
  {
    return $this->connected($tenant) ?? (config('saas.payment_gateway') === 'fake' ? $this->driver('fake') : null);
  }
}
