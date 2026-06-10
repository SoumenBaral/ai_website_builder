@php $align = $props['align'] ?? 'center'; @endphp
<section class="py-20 px-6 {{ $align === 'left' ? 'text-left' : 'text-center' }} text-white" style="background-color: var(--primary); font-family: var(--font-body);">
    <div class="max-w-3xl {{ $align === 'left' ? '' : 'mx-auto' }} px-6">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @if(!empty($props['subheading']))
            <p class="text-lg mb-8 opacity-90">{{ $props['subheading'] }}</p>
        @endif

        @if(!empty($props['ctaText']))
            <a href="{{ $props['ctaHref'] ?? '#' }}" class="inline-block px-8 py-3 font-semibold transition-opacity hover:opacity-90" style="background-color: white; color: var(--primary); border-radius: var(--radius);">
                {{ $props['ctaText'] }}
            </a>
        @elseif(!empty($props['button']['label']))
            <a href="{{ $props['button']['url'] ?? '#' }}" class="inline-block px-8 py-3 font-semibold transition-opacity hover:opacity-90" style="background-color: white; color: var(--primary); border-radius: var(--radius);">
                {{ $props['button']['label'] }}
            </a>
        @endif
    </div>
</section>
