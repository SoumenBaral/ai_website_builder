@php $align = $props['align'] ?? 'center'; @endphp
{{-- Mirrors resources/js/Components/builder/blocks/BlockCta.vue: keep the two in step. --}}
<section class="relative py-20 px-6 overflow-hidden {{ $align === 'left' ? 'text-left' : 'text-center' }}" style="background-color: var(--primary); color: var(--on-primary); font-family: var(--font-body);">
    @if(!empty($props['image']))
        <img {!! \App\Support\SiteHtml::img($props['image'], '100vw') !!} alt="" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/50"></div>
    @endif
    <div class="relative z-10 max-w-3xl {{ $align === 'left' ? '' : 'mx-auto' }}">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @if(!empty($props['subheading']))
            <p class="text-lg mb-8 opacity-90">{{ $props['subheading'] }}</p>
        @endif

        @if(!empty($props['ctaText']))
            <a href="{{ \App\Support\SiteHtml::href($props['ctaHref'] ?? '#') }}" class="inline-block px-8 py-3 font-medium text-lg transition-opacity hover:opacity-90" style="background-color: var(--on-primary); color: var(--primary); border-radius: var(--radius);">
                {{ $props['ctaText'] }}
            </a>
        @elseif(!empty($props['button']['label']))
            <a href="{{ \App\Support\SiteHtml::href($props['button']['url'] ?? '#') }}" class="inline-block px-8 py-3 font-medium text-lg transition-opacity hover:opacity-90" style="background-color: var(--on-primary); color: var(--primary); border-radius: var(--radius);">
                {{ $props['button']['label'] }}
            </a>
        @endif
    </div>
</section>
