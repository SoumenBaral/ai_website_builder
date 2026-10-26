<nav id="main-nav" class="fixed top-0 left-0 right-0 z-50 w-full" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <!-- Main bar -->
    <div class="flex items-center justify-between px-6 py-4" style="box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        <a href="#" class="flex min-w-0 items-center gap-3" style="color: inherit; text-decoration: none;">
            @if(!empty($props['logo']))
                @php
                    // Logos that include the name read better a little larger.
                    $logoSize = $props['logoSize'] ?? (!empty($props['hideBrandText']) ? 'md' : 'sm');
                    [$logoH, $logoHMobile, $logoW] = ['sm' => ['2.25rem', '2rem', '220px'], 'md' => ['2.75rem', '2.25rem', '280px'], 'lg' => ['3.25rem', '2.5rem', '320px'], 'xl' => ['3.75rem', '2.75rem', '380px']][$logoSize] ?? ['2.25rem', '2rem', '220px'];
                @endphp
                <img src="{{ $props['logo'] }}" alt="{{ $props['brand'] ?? 'Logo' }}" class="site-logo" style="--logo-h: {{ $logoH }}; --logo-h-sm: {{ $logoHMobile }}; height: var(--logo-h-sm); width: auto; max-width: min({{ $logoW }}, 60vw); object-fit: contain;">
                <style>@media (min-width: 768px) { .site-logo { height: var(--logo-h) !important; } }</style>
            @endif
            @if(empty($props['logo']) || (!empty($props['brand']) && empty($props['hideBrandText'])))
                <span class="text-xl font-bold" style="font-family: var(--font-heading);">{{ $props['brand'] ?? '' }}</span>
            @endif
        </a>

        <!-- Desktop links -->
        <div class="hidden md:flex items-center gap-6">
            @if(!empty($props['links']))
                @foreach($props['links'] as $link)
                    <a href="{{ \App\Support\SiteHtml::href($link['href'] ?? $link['url'] ?? '#') }}" class="text-sm hover:opacity-80 transition-opacity" style="color: var(--text);">
                        {{ $link['label'] ?? '' }}
                    </a>
                @endforeach
            @endif

            @if(!empty($props['ctaText']))
                <a href="{{ \App\Support\SiteHtml::href($props['ctaHref'] ?? '#') }}" class="px-5 py-2 text-sm font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                    {{ $props['ctaText'] }}
                </a>
            @elseif(!empty($props['cta']['label']))
                <a href="{{ \App\Support\SiteHtml::href($props['cta']['url'] ?? '#') }}" class="px-5 py-2 text-sm font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
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
    <div id="mobile-menu" class="hidden md:hidden border-t px-6 py-4" style="background-color: var(--bg); border-color: var(--line);">
        <div class="flex flex-col gap-3">
            @if(!empty($props['links']))
                @foreach($props['links'] as $link)
                    <a href="{{ \App\Support\SiteHtml::href($link['href'] ?? $link['url'] ?? '#') }}" class="text-sm py-2 hover:opacity-80 transition-opacity" style="color: var(--text);">
                        {{ $link['label'] ?? '' }}
                    </a>
                @endforeach
            @endif

            @if(!empty($props['ctaText']))
                <a href="{{ \App\Support\SiteHtml::href($props['ctaHref'] ?? '#') }}" class="mt-1 block text-center px-5 py-2.5 text-sm font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                    {{ $props['ctaText'] }}
                </a>
            @elseif(!empty($props['cta']['label']))
                <a href="{{ \App\Support\SiteHtml::href($props['cta']['url'] ?? '#') }}" class="mt-1 block text-center px-5 py-2.5 text-sm font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                    {{ $props['cta']['label'] }}
                </a>
            @endif
        </div>
    </div>
</nav>
<!-- Spacer for the fixed navbar: as tall as the bar really is (a large logo makes it taller than 64px). -->
<div id="main-nav-spacer" style="height: 64px;"></div>
<script>
(function () {
    var bar = document.querySelector('#main-nav > div'), spacer = document.getElementById('main-nav-spacer');
    if (!bar || !spacer) return;
    var fit = function () { spacer.style.height = bar.offsetHeight + 'px'; };
    fit();
    if (window.ResizeObserver) new ResizeObserver(fit).observe(bar); else window.addEventListener('resize', fit);
})();
</script>
