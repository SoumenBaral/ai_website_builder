<section class="relative min-h-[500px] flex items-center" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    @if(!empty($props['image']))
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $props['image'] }}');"></div>
        <div class="absolute inset-0 bg-black/50"></div>
    @elseif(!empty($props['backgroundImage']))
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $props['backgroundImage'] }}');"></div>
        <div class="absolute inset-0 bg-black/50"></div>
    @endif

    <div class="relative z-10 w-full max-w-4xl mx-auto px-6 py-20 {{ ($props['align'] ?? 'center') === 'center' ? 'text-center' : 'text-left' }}">
        <h1 class="text-4xl md:text-5xl font-bold mb-4" style="font-family: var(--font-heading);">
            {{ $props['heading'] ?? 'Welcome' }}
        </h1>

        @if(!empty($props['subheading']))
            <p class="text-lg md:text-xl mb-8 opacity-90">
                {{ $props['subheading'] }}
            </p>
        @endif

        @if(!empty($props['ctaText']))
            <a href="{{ $props['ctaHref'] ?? '#' }}" class="inline-block px-8 py-3 text-white font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                {{ $props['ctaText'] }}
            </a>
        @elseif(!empty($props['cta']['label']))
            <a href="{{ $props['cta']['url'] ?? '#' }}" class="inline-block px-8 py-3 text-white font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                {{ $props['cta']['label'] }}
            </a>
        @endif
    </div>
</section>
