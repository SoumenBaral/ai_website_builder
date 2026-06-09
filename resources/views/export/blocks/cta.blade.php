<section class="py-20 px-6 text-center text-white" style="background-color: var(--primary); font-family: var(--font-body);">
    <div class="max-w-3xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @if(!empty($props['subheading']))
            <p class="text-lg mb-8 opacity-90">
                {{ $props['subheading'] }}
            </p>
        @endif

        @if(!empty($props['button']))
            <a href="{{ $props['button']['url'] ?? '#' }}" class="inline-block px-8 py-3 font-semibold transition-opacity hover:opacity-90" style="background-color: white; color: var(--primary); border-radius: var(--radius);">
                {{ $props['button']['label'] ?? 'Get Started' }}
            </a>
        @endif
    </div>
</section>
