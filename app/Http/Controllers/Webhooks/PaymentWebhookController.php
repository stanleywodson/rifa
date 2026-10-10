<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Payments\Jobs\ProcessPaymentWebhookJob;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\PaymentGatewayManager;
use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Recebe webhooks do gateway: valida autenticidade, grava de forma idempotente
 * (UNIQUE origin+event_id) e processa em fila. Responde 200 rapidamente para
 * evitar reenvios; duplicatas são ignoradas.
 */
class PaymentWebhookController extends Controller
{
  public function __invoke(Request $request, string $gateway, PaymentGatewayManager $gateways)
  {
    try {
      $driver = $gateways->driver($gateway);
    } catch (InvalidArgumentException) {
      abort(404);
    }

    $payload = $driver->parseWebhook($request);
    if (!$payload) {
      abort(401, 'Assinatura inválida.');
    }

    try {
      $event = WebhookEvent::create([
        'origin' => $driver->name(),
        'event_id' => $payload->eventId,
        'event_type' => $payload->eventType,
        'payload' => $payload->raw,
      ]);
    } catch (UniqueConstraintViolationException) {
      return response()->json(['received' => true, 'duplicate' => true]);
    }

    ProcessPaymentWebhookJob::dispatch($event->id, $payload->gatewayPaymentId, $payload->status?->value);

    return response()->json(['received' => true]);
  }
}
