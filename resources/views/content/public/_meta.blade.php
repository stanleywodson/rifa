{{-- Metadados SEO / Open Graph (preview no WhatsApp) --}}
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
@if (!empty($image))<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="{{ url()->current() }}">
