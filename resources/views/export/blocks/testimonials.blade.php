@php
    $cols = $props['columns'] ?? 3;
    $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'][$cols] ?? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
@endphp
{{-- Mirrors resources/js/Components/builder/blocks/BlockTestimonials.vue: keep the two in step. --}}
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="grid {{ $gridClass }} gap-8">
            @if(!empty($props['items']))
                @foreach($props['items'] as $item)
                    <div class="p-8 border flex flex-col transition-shadow hover:shadow-lg" style="border-radius: var(--radius); background-color: var(--surface); border-color: var(--line);">
                        <div class="text-4xl mb-4 opacity-20" style="color: var(--primary);" aria-hidden="true">&ldquo;</div>
                        <p class="italic opacity-80 flex-1 mb-6">{{ $item['quote'] ?? '' }}</p>
                        <div class="flex items-center gap-3 mt-auto">
                            @if(!empty($item['avatar']))
                                <div class="w-12 h-12 rounded-full bg-gray-300 overflow-hidden flex-shrink-0">
                                    <img {!! \App\Support\SiteHtml::img($item['avatar'], '48px', [96, 160]) !!} alt="{{ $item['author'] ?? '' }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold flex-shrink-0" style="background-color: var(--primary); color: var(--on-primary);" aria-hidden="true">{{ mb_substr($item['author'] ?? '', 0, 1) }}</div>
                            @endif
                            <div>
                                <p class="font-semibold text-sm" style="font-family: var(--font-heading);">{{ $item['author'] ?? '' }}</p>
                                @if(!empty($item['role']))
                                    <p class="text-sm opacity-60">{{ $item['role'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
