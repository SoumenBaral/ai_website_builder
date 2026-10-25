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
    <link rel="preconnect" href="https://images.pexels.com">
    @php
        // Each family once (heading and body are often the same font), loaded without blocking first paint.
        $fontsHref = 'https://fonts.googleapis.com/css2?' . implode('&', array_map(fn ($f) => 'family=' . urlencode($f) . ':wght@400;500;600;700', $fonts ?? [$theme['fontHeading'], $theme['fontBody']])) . '&display=swap';
    @endphp
    <link rel="preload" as="style" href="{{ $fontsHref }}" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ $fontsHref }}"></noscript>
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
@if(!empty($animationsHref))
    <link rel="stylesheet" href="{{ $animationsHref }}">
@elseif(!empty($animations))
    <style>{!! $animations !!}</style>
@endif
@if(!empty($shop['cssHref']))
    <link rel="stylesheet" href="{{ $shop['cssHref'] }}">
@elseif(!empty($shop['css']))
    <style>{!! $shop['css'] !!}</style>
@endif
</head>
<body @if(!empty($motion)) class="anim-clip" @endif>
{!! $body !!}
@if(!empty($animationRuntimeHref))
<script defer src="{{ $animationRuntimeHref }}"></script>
<script>document.addEventListener('DOMContentLoaded', function () { window.SiteAnim && window.SiteAnim.init(); });</script>
@elseif(!empty($animationRuntime))
<script>
{!! $animationRuntime !!}
window.SiteAnim && window.SiteAnim.init();
</script>
@endif
@if(!empty($shop))
<script>window.__SHOP__ = {!! $shop['config'] !!};</script>
@if(!empty($shop['runtimeHref']))
<script defer src="{{ $shop['runtimeHref'] }}"></script>
@else
<script>
{!! $shop['runtime'] !!}
</script>
@endif
@endif
@if(!empty($beacon))
{!! $beacon !!}
@endif
</body>
</html>
