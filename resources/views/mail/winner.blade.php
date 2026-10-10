<x-mail::message>
# Parabéns, {{ $draw->customer->name }}!

Seu número **{{ $draw->raffle->formatNumber($draw->drawn_number) }}** foi o contemplado na rifa **{{ $draw->raffle->title }}**.

O organizador **{{ $draw->raffle->tenant->name }}** entrará em contato pelo telefone ou e-mail cadastrados para combinar a entrega do prêmio.

<x-mail::button :url="route('public.draw', [$draw->raffle->tenant->slug, $draw->raffle->slug])">
Ver prova do sorteio
</x-mail::button>
</x-mail::message>
