<x-mail::message>
# Nova venda confirmada

A rifa **{{ $order->raffle->title }}** recebeu um pagamento de **{{ money_br($order->total_cents) }}** ({{ $order->quantity }} número(s)).

<x-mail::button :url="route('painel.pedidos.show', $order)">
Ver pedido
</x-mail::button>
</x-mail::message>
