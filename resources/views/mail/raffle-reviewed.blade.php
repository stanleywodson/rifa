<x-mail::message>
@if ($decision === 'approved')
# Rifa aprovada!

A rifa **{{ $raffle->title }}** foi aprovada. Os números estão sendo gerados e a página pública ficará disponível em instantes.

<x-mail::button :url="route('public.raffle', [$raffle->tenant->slug, $raffle->slug])">
Abrir página da rifa
</x-mail::button>
@elseif ($decision === 'rejected')
# Rifa reprovada

A rifa **{{ $raffle->title }}** foi reprovada pela análise da plataforma.
@else
# Ajustes solicitados

A análise da rifa **{{ $raffle->title }}** solicitou ajustes antes da publicação.
@endif

@if ($raffle->review_notes)
<x-mail::panel>
{{ $raffle->review_notes }}
</x-mail::panel>
@endif

<x-mail::button :url="route('painel.rifas.show', $raffle)" color="success">
Ver no painel
</x-mail::button>
</x-mail::message>
