@php
    $cols = $props['columns'] ?? 3;
    $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'][$cols] ?? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
@endphp
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="grid {{ $gridClass }} gap-8 items-start">
            @if(!empty($props['plans']))
                @foreach($props['plans'] as $plan)
                    <div
                        class="p-6 flex flex-col"
                        style="border-radius: var(--radius); background-color: var(--bg);{{ !empty($plan['highlighted']) ? ' border: 2px solid var(--primary); box-shadow: 0 0 0 2px var(--primary);' : ' border: 1px solid rgba(0,0,0,0.1);' }}"
                    >
                        <h3 class="text-xl font-semibold mb-2" style="font-family: var(--font-heading);">
                            {{ $plan['name'] ?? '' }}
                        </h3>

                        <div class="mb-4">
                            <span class="text-4xl font-bold" style="color: var(--primary);">
                                {{ $plan['price'] ?? '' }}
                            </span>
                            @if(!empty($plan['period']))
                                <span class="text-sm opacity-60">/{{ $plan['period'] }}</span>
                            @endif
                        </div>

                        @if(!empty($plan['features']))
                            <ul class="mb-6 space-y-2 flex-1">
                                @foreach($plan['features'] as $feature)
                                    <li class="flex items-center gap-2">
                                        <span style="color: var(--primary);">&#10003;</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($plan['ctaText']))
                            <a href="#" class="block text-center py-2 px-4 font-semibold" style="background-color: var(--primary); color: var(--bg); border-radius: var(--radius);">
                                {{ $plan['ctaText'] }}
                            </a>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
