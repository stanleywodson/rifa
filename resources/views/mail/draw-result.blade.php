<x-mail::message>
# Sorteio realizado

O sorteio da rifa **{{ $draw->raffle->title }}** foi realizado.

<x-mail::panel>
Número contemplado: **{{ $draw->raffle->formatNumber($draw->drawn_number) }}**
</x-mail::panel>

Não foi desta vez — obrigado por participar! A prova completa do sorteio está publicada para conferência.

<x-mail::button :url="route('public.draw', [$draw->raffle->tenant->slug, $draw->raffle->slug])">
Ver prova do sorteio
</x-mail::button>
</x-mail::message>
