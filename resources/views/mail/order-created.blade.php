<x-mail::message>
# Seu Pix foi gerado

Olá, {{ $order->customer->name }}!

Recebemos seu pedido de **{{ $order->quantity }} número(s)** na rifa **{{ $order->raffle->title }}**.

<x-mail::panel>
Números reservados: **{{ implode(', ', $order->numbersFormatted()) }}**<br>
Total: **{{ money_br($order->total_cents) }}**<br>
Pague até: **{{ $order->expires_at->format('d/m/Y H:i') }}**
</x-mail::panel>

Após esse horário a reserva expira e os números voltam a ficar disponíveis.

<x-mail::button :url="route('public.order', $order->code)">
Pagar com Pix
</x-mail::button>

Código do pedido: `{{ $order->code }}`
</x-mail::message>
