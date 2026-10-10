<?php

namespace App\Http\Controllers\PublicSite;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\Jobs\ProcessPaymentWebhookJob;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

/**
 * Página do pedido (Pix / comprovante). O código ULID do pedido funciona
 * como segredo de acesso: não é sequencial nem adivinhável.
 */
class OrderPageController extends Controller
{
  public function show(Order $order)
  {
    $order->load(['raffle.tenant', 'raffle.media', 'customer', 'items', 'payment']);

    return view('content.public.order', [
      'pageConfigs' => ['myLayout' => 'front'],
      'order' => $order,
      'raffle' => $order->raffle,
      'canSimulate' => $this->canSimulate($order),
    ]);
  }

  public function status(Order $order)
  {
    return response()->json([
      'status' => $order->status->value,
      'label' => $order->status->label(),
      'expires_at' => $order->expires_at->toIso8601String(),
    ]);
  }

  /**
   * Somente ambiente local + gateway simulado: dispara o mesmo fluxo do webhook.
   */
  public function simulate(Order $order)
  {
    abort_unless($this->canSimulate($order), 404);

    $event = WebhookEvent::create([
      'origin' => 'fake',
      'event_id' => 'sim_' . Str::ulid(),
      'event_type' => 'PAYMENT_RECEIVED',
      'payload' => ['payment' => ['id' => $order->payment->gateway_id], 'simulated' => true],
    ]);
    ProcessPaymentWebhookJob::dispatchSync($event->id, $order->payment->gateway_id, 'paid');

    return redirect()->route('public.order', $order->code)->with('success', 'Pagamento simulado com sucesso.');
  }

  protected function canSimulate(Order $order): bool
  {
    return app()->isLocal() && $order->payment?->gateway === (new FakeGateway)->name() && $order->isAwaitingPayment();
  }
}
