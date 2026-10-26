{{-- Mirrors resources/js/Components/builder/blocks/BlockFaq.vue: keep the two in step. --}}
@php
    $image = $props['image'] ?? null;
    $wClass = $image ? 'max-w-6xl' : (['sm' => 'max-w-xl', 'md' => 'max-w-3xl', 'lg' => 'max-w-5xl', 'full' => 'max-w-6xl'][$props['maxWidth'] ?? 'md'] ?? 'max-w-3xl');
@endphp
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <style>
        .faq-item > summary { list-style: none; }
        .faq-item > summary::-webkit-details-marker { display: none; }
        .faq-item[open] .faq-plus { transform: rotate(45deg); }
    </style>
    <div class="{{ $wClass }} mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="{{ $image ? 'grid grid-cols-1 md:grid-cols-2 gap-10 items-start' : '' }}">
            @if($image)
                <img {!! \App\Support\SiteHtml::img($image, '(min-width: 768px) 50vw, 100vw') !!} alt="" loading="lazy" decoding="async" class="w-full h-auto object-cover hidden md:block" style="{{ \App\Support\SiteHtml::imageStyle($props) }}">
            @endif
            <div class="space-y-4">
                @foreach($props['items'] ?? [] as $item)
                    <details class="faq-item border border-gray-200 overflow-hidden" style="border-radius: var(--radius);">
                        <summary class="w-full flex items-center justify-between p-5 text-left font-semibold cursor-pointer hover:bg-black/5 transition-colors" style="font-family: var(--font-heading);">
                            <span>{{ $item['question'] ?? '' }}</span>
                            <span class="faq-plus ml-4 text-xl transition-transform duration-200 flex-shrink-0" aria-hidden="true">+</span>
                        </summary>
                        <div class="px-5 pb-5">
                            <p class="opacity-70 leading-relaxed">{{ $item['answer'] ?? '' }}</p>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
</section>
