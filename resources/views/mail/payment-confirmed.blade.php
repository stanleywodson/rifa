<x-mail::message>
# Pagamento confirmado!

Olá, {{ $order->customer->name }}! Seu pagamento de **{{ money_br($order->total_cents) }}** foi confirmado.

<x-mail::panel>
Seus números na rifa **{{ $order->raffle->title }}**:<br>
**{{ implode(', ', $order->numbersFormatted()) }}**
</x-mail::panel>

@if ($order->raffle->draw_at)
O sorteio está previsto para **{{ $order->raffle->draw_at->format('d/m/Y \à\s H:i') }}**.
@endif

<x-mail::button :url="route('public.order', $order->code)">
Ver comprovante
</x-mail::button>

Guarde o código do pedido: `{{ $order->code }}`. Você também pode consultar seus números em [Meus números]({{ route('public.my-tickets') }}).
</x-mail::message>
