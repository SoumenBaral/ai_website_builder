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

        <div class="grid {{ $gridClass }} gap-8">
            @if(!empty($props['items']))
                @foreach($props['items'] as $item)
                    <div class="p-6" style="border-radius: var(--radius); background-color: var(--bg); border: 1px solid rgba(0,0,0,0.1);">
                        <p class="italic text-lg mb-4 opacity-90">
                            "{{ $item['quote'] ?? '' }}"
                        </p>

                        <div>
                            <span class="font-semibold" style="font-family: var(--font-heading);">
                                {{ $item['author'] ?? '' }}
                            </span>

                            @if(!empty($item['role']))
                                <span class="block text-sm opacity-60">{{ $item['role'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
