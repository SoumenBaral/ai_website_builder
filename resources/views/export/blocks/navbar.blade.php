<nav class="w-full px-6 py-4 flex items-center justify-between" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="text-xl font-bold" style="font-family: var(--font-heading);">
        {{ $props['brand'] ?? 'Brand' }}
    </div>

    <div class="hidden md:flex items-center gap-6">
        @if(!empty($props['links']))
            @foreach($props['links'] as $link)
                <a href="{{ $link['url'] ?? '#' }}" class="hover:opacity-80 transition-opacity" style="color: var(--text);">
                    {{ $link['label'] ?? '' }}
                </a>
            @endforeach
        @endif

        @if(!empty($props['cta']))
            <a href="{{ $props['cta']['url'] ?? '#' }}" class="px-5 py-2 text-white font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                {{ $props['cta']['label'] ?? 'Get Started' }}
            </a>
        @endif
    </div>
</nav>
