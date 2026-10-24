<!DOCTYPE html>
<html lang="{{ $meta['language'] ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $meta['title'] ?? 'My Website' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? '' }}">
    {!! $head ?? '' !!}
    @if(!empty($tailwind['inline']))
    <style>{!! $tailwind['inline'] !!}</style>
    @elseif(!empty($tailwind['href']))
    <link rel="stylesheet" href="{{ $tailwind['href'] }}">
    @else
    {{-- Fallback only if the site stylesheet was never built (npm run build:site). --}}
    <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($theme['fontHeading']) }}:wght@400;600;700&family={{ urlencode($theme['fontBody']) }}:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        :root {
            --primary: {{ $theme['primaryColor'] }};
            --secondary: {{ $theme['secondaryColor'] ?? $theme['primaryColor'] }};
            --bg: {{ $theme['backgroundColor'] }};
            --text: {{ $theme['textColor'] }};
            --font-heading: '{{ $theme['fontHeading'] }}', sans-serif;
            --font-body: '{{ $theme['fontBody'] }}', sans-serif;
            --radius: {{ ['none' => '0', 'sm' => '0.25rem', 'md' => '0.5rem', 'lg' => '0.75rem', 'xl' => '1rem', 'full' => '9999px'][$theme['radius']] ?? '0.5rem' }};
            {{-- Text on a --primary fill, card fills and hairlines (re-derived per coloured section). --}}
            --on-primary: {{ \App\Services\Ai\Color::readableOn(\App\Services\Ai\Color::normalize($theme['primaryColor']) ?? '#2563eb') }};
            @foreach(\App\Services\Ai\Color::surfaceVars(\App\Services\Ai\Color::normalize($theme['backgroundColor']) ?? '#ffffff') as $var => $value)--{{ $var }}: {{ $value }}; @endforeach
        }
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            margin: 0;
        }
    </style>
@if(!empty($animations))
    <style>{!! $animations !!}</style>
@endif
@if(!empty($shop))
    <style>{!! $shop['css'] !!}</style>
@endif
</head>
<body @if(!empty($animations)) class="anim-clip" @endif>
{!! $body !!}
@if(!empty($animationRuntime))
<script>
{!! $animationRuntime !!}
window.SiteAnim && window.SiteAnim.init();
</script>
@endif
@if(!empty($shop))
<script>window.__SHOP__ = {!! $shop['config'] !!};</script>
<script>
{!! $shop['runtime'] !!}
</script>
@endif
@if(!empty($beacon))
{!! $beacon !!}
@endif
</body>
</html>
