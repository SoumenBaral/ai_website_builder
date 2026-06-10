<nav id="main-nav" class="fixed top-0 left-0 right-0 z-50 w-full" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <!-- Main bar -->
    <div class="flex items-center justify-between px-6 py-4" style="box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        <div class="text-xl font-bold" style="font-family: var(--font-heading);">
            {{ $props['brand'] ?? 'Brand' }}
        </div>

        <!-- Desktop links -->
        <div class="hidden md:flex items-center gap-6">
            @if(!empty($props['links']))
                @foreach($props['links'] as $link)
                    <a href="{{ $link['href'] ?? $link['url'] ?? '#' }}" class="text-sm hover:opacity-80 transition-opacity" style="color: var(--text);">
                        {{ $link['label'] ?? '' }}
                    </a>
                @endforeach
            @endif

            @if(!empty($props['ctaText']))
                <a href="{{ $props['ctaHref'] ?? '#' }}" class="px-5 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                    {{ $props['ctaText'] }}
                </a>
            @elseif(!empty($props['cta']['label']))
                <a href="{{ $props['cta']['url'] ?? '#' }}" class="px-5 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                    {{ $props['cta']['label'] }}
                </a>
            @endif
        </div>

        <!-- Hamburger button -->
        <button
            onclick="document.getElementById('mobile-menu').classList.toggle('hidden'); this.querySelector('.icon-open').classList.toggle('hidden'); this.querySelector('.icon-close').classList.toggle('hidden');"
            class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg transition-colors"
            style="color: var(--text);"
            aria-label="Toggle menu"
        >
            <svg class="icon-open w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
            <svg class="icon-close w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden border-t px-6 py-4" style="background-color: var(--bg); border-color: rgba(0,0,0,0.06);">
        <div class="flex flex-col gap-3">
            @if(!empty($props['links']))
                @foreach($props['links'] as $link)
                    <a href="{{ $link['href'] ?? $link['url'] ?? '#' }}" class="text-sm py-2 hover:opacity-80 transition-opacity" style="color: var(--text);">
                        {{ $link['label'] ?? '' }}
                    </a>
                @endforeach
            @endif

            @if(!empty($props['ctaText']))
                <a href="{{ $props['ctaHref'] ?? '#' }}" class="mt-1 block text-center px-5 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                    {{ $props['ctaText'] }}
                </a>
            @elseif(!empty($props['cta']['label']))
                <a href="{{ $props['cta']['url'] ?? '#' }}" class="mt-1 block text-center px-5 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                    {{ $props['cta']['label'] }}
                </a>
            @endif
        </div>
    </div>
</nav>
<!-- Spacer for fixed navbar -->
<div style="height: 64px;"></div>
