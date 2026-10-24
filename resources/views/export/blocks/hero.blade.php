@php
    $layout = $props['layout'] ?? $props['align'] ?? 'center';
    $isSplit = in_array($layout, ['split-left', 'split-right']);
    $overlay = ($props['overlayOpacity'] ?? 50) / 100;
    $minH = ['sm' => '300px', 'md' => '400px', 'lg' => '500px', 'full' => '100vh'][$props['minHeight'] ?? 'lg'] ?? '500px';
    $textAlign = $isSplit ? 'text-left' : ($layout === 'left' ? 'text-left' : ($layout === 'right' ? 'text-right' : 'text-center'));
    $image = $props['image'] ?? $props['backgroundImage'] ?? '';
@endphp

@if($isSplit)
{{-- Phones: photo on top, text below (no forced full-screen height). Tablets and up: side by side. --}}
<section class="flex flex-col md:flex-row items-stretch md:min-h-[{{ $minH }}]">
    {{-- Image side --}}
    {{-- Focus near the top: people's heads (and most subjects) sit in the upper part of a photo. --}}
    <div class="block w-full h-80 sm:h-[28rem] md:h-auto md:w-1/2 bg-cover order-1 {{ $layout === 'split-left' ? 'md:order-1' : 'md:order-2' }}"
         style="background-image: url('{{ $image }}'); background-position: 50% 18%; {{ !$image ? 'background-color: var(--secondary, #e2e8f0);' : '' }}"></div>
    {{-- Text side --}}
    <div class="w-full md:w-1/2 flex items-center order-2 {{ $layout === 'split-left' ? 'md:order-2' : 'md:order-1' }}"
         style="background-color: var(--bg); color: var(--text);">
        <div class="w-full max-w-xl mx-auto px-6 py-12 sm:px-8 md:py-16 lg:px-12 {{ $textAlign }}">
            <h1 class="text-4xl md:text-5xl font-bold mb-4" style="font-family: var(--font-heading);">
                {{ $props['heading'] ?? 'Welcome' }}
            </h1>
            @if(!empty($props['subheading']))
                <p class="text-lg md:text-xl mb-8 opacity-90">{{ $props['subheading'] }}</p>
            @endif
            @if(!empty($props['ctaText']))
                <a href="{{ $props['ctaHref'] ?? '#' }}" class="inline-block px-8 py-3 font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                    {{ $props['ctaText'] }}
                </a>
            @endif
        </div>
    </div>
</section>
@else
<section class="relative flex items-center {{ $textAlign }}" style="min-height: {{ $minH }}; background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    @if(!empty($image))
        <div class="absolute inset-0 bg-cover" style="background-image: url('{{ $image }}'); background-position: 50% 30%;"></div>
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
            <a href="{{ $props['ctaHref'] ?? '#' }}" class="inline-block px-8 py-3 font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                {{ $props['ctaText'] }}
            </a>
        @elseif(!empty($props['cta']['label']))
            <a href="{{ $props['cta']['url'] ?? '#' }}" class="inline-block px-8 py-3 font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                {{ $props['cta']['label'] }}
            </a>
        @endif
    </div>
</section>
@endif
