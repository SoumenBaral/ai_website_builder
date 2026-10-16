<!DOCTYPE html>
<html lang="{{ $meta['language'] ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $meta['title'] ?? 'My Website' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? '' }}">
    {!! $head ?? '' !!}
    <script src="https://cdn.tailwindcss.com"></script>
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
