@php
    $cols = $props['columns'] ?? 3;
    $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'][$cols] ?? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
    // Plan buttons lead to the booking / contact form when the page has one.
    $planHref = $props['ctaHref'] ?? '#contact';
@endphp
{{-- Mirrors resources/js/Components/builder/blocks/BlockPricing.vue: keep the two in step. --}}
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['image']))
            <img {!! \App\Support\SiteHtml::img($props['image'], '(min-width: 1152px) 1152px, 100vw') !!} alt="" loading="lazy" decoding="async" class="w-full h-48 object-cover mb-10" style="{{ \App\Support\SiteHtml::imageStyle($props) }}">
        @endif

        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="grid {{ $gridClass }} gap-8 items-start">
            @foreach($props['plans'] ?? [] as $plan)
                @php $hot = !empty($plan['highlighted']); @endphp
                <div
                    class="p-8 border-2 flex flex-col overflow-hidden transition-shadow duration-300 hover:shadow-xl"
                    style="border-radius: var(--radius); background-color: var(--surface); border-color: {{ $hot ? 'var(--primary)' : 'var(--line)' }}; box-shadow: {{ $hot ? '0 8px 30px rgba(0,0,0,0.12)' : '0 2px 8px rgba(0,0,0,0.06)' }};"
                >
                    @if($hot)
                        <div class="text-xs font-bold uppercase tracking-wider text-center mb-4 -mt-4 -mx-8 py-2" style="background-color: var(--primary); color: var(--on-primary);">Most Popular</div>
                    @endif

                    <h3 class="text-xl font-semibold text-center mb-2" style="font-family: var(--font-heading);">
                        {{ $plan['name'] ?? '' }}
                    </h3>

                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold" style="font-family: var(--font-heading); color: var(--primary);">{{ $plan['price'] ?? '' }}</span>
                        @if(!empty($plan['period']))
                            <span class="text-sm opacity-60 ml-1">{{ $plan['period'] }}</span>
                        @endif
                    </div>

                    @if(!empty($plan['features']))
                        <ul class="space-y-3 mb-8 flex-1">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-start gap-2 text-sm">
                                    <span class="mt-0.5" style="color: var(--primary);">&#10003;</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if(!empty($plan['ctaText']))
                        <a href="{{ \App\Support\SiteHtml::href($plan['ctaHref'] ?? $planHref) }}"
                           class="block w-full py-3 px-6 font-semibold text-center transition-opacity hover:opacity-90"
                           style="font-family: var(--font-heading); border-radius: var(--radius); {{ $hot ? 'background-color: var(--primary); color: var(--on-primary); border: 2px solid var(--primary);' : 'background-color: transparent; color: var(--primary); border: 2px solid var(--primary);' }}">
                            {{ $plan['ctaText'] }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
