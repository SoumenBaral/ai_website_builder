<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @php
            $cols = $props['columns'] ?? 4;
            $gridClass = [2 => 'grid-cols-2', 3 => 'grid-cols-2 md:grid-cols-3', 4 => 'grid-cols-2 md:grid-cols-4', 5 => 'grid-cols-2 md:grid-cols-5', 6 => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6'][$cols] ?? 'grid-cols-2 md:grid-cols-4';
        @endphp
        <div class="grid {{ $gridClass }} gap-8">
            @if(!empty($props['items']))
                @foreach($props['items'] as $item)
                    <div class="text-center">
                        <div class="text-5xl font-bold mb-2" style="color: var(--primary); font-family: var(--font-heading);">
                            {{ $item['value'] ?? '' }}
                        </div>
                        <div class="text-sm uppercase tracking-wide opacity-70">
                            {{ $item['label'] ?? '' }}
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
