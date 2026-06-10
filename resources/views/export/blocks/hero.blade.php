@php
    $layout = $props['layout'] ?? $props['align'] ?? 'center';
    $isSplit = in_array($layout, ['split-left', 'split-right']);
    $overlay = ($props['overlayOpacity'] ?? 50) / 100;
    $minH = ['sm' => '300px', 'md' => '400px', 'lg' => '500px', 'full' => '100vh'][$props['minHeight'] ?? 'lg'] ?? '500px';
    $textAlign = $isSplit ? 'text-left' : ($layout === 'left' ? 'text-left' : ($layout === 'right' ? 'text-right' : 'text-center'));
    $image = $props['image'] ?? $props['backgroundImage'] ?? '';
@endphp

@if($isSplit)
<section class="flex items-stretch" style="min-height: {{ $minH }};">
    {{-- Image side --}}
    <div class="hidden md:block w-1/2 bg-cover bg-center {{ $layout === 'split-left' ? 'order-1' : 'order-2' }}"
         style="background-image: url('{{ $image }}'); {{ !$image ? 'background-color: var(--secondary, #e2e8f0);' : '' }}"></div>
    {{-- Text side --}}
    <div class="w-full md:w-1/2 flex items-center {{ $layout === 'split-left' ? 'order-2' : 'order-1' }}"
         style="background-color: var(--bg); color: var(--text);">
        <div class="w-full max-w-xl mx-auto px-8 py-16 lg:px-12 {{ $textAlign }}">
            <h1 class="text-4xl md:text-5xl font-bold mb-4" style="font-family: var(--font-heading);">
                {{ $props['heading'] ?? 'Welcome' }}
            </h1>
            @if(!empty($props['subheading']))
                <p class="text-lg md:text-xl mb-8 opacity-90">{{ $props['subheading'] }}</p>
            @endif
            @if(!empty($props['ctaText']))
                <a href="{{ $props['ctaHref'] ?? '#' }}" class="inline-block px-8 py-3 text-white font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                    {{ $props['ctaText'] }}
                </a>
            @endif
        </div>
    </div>
</section>
@else
<section class="relative flex items-center {{ $textAlign }}" style="min-height: {{ $minH }}; background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    @if(!empty($image))
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $image }}');"></div>
        <div class="absolute inset-0" style="background-color: rgba(0,0,0,{{ $overlay }});"></div>
    @endif
    <div class="relative z-10 w-full max-w-4xl mx-auto px-6 py-20 {{ $textAlign }}">
        <h1 class="text-4xl md:text-5xl font-bold mb-4 {{ !empty($image) ? 'text-white' : '' }}" style="font-family: var(--font-heading);">
            {{ $props['heading'] ?? 'Welcome' }}
        </h1>
        @if(!empty($props['subheading']))
            <p class="text-lg md:text-xl mb-8 opacity-90 {{ !empty($image) ? 'text-white/80' : '' }}">{{ $props['subheading'] }}</p>
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
@endif
