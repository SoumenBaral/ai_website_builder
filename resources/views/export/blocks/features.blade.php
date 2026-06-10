@php
    $cols = $props['columns'] ?? 3;
    $align = $props['align'] ?? 'center';
    $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3', 4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'][$cols] ?? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
@endphp
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['image']))
            <img src="{{ $props['image'] }}" alt="" class="w-full h-48 object-cover mb-10" style="border-radius: var(--radius);">
        @endif

        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold {{ $align === 'left' ? 'text-left' : 'text-center' }} mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif
        <div class="grid {{ $gridClass }} gap-8">
            @if(!empty($props['items']))
                @foreach($props['items'] as $item)
                    <div class="overflow-hidden border border-gray-100 {{ $align === 'left' ? 'text-left' : 'text-center' }}" style="border-radius: var(--radius); background-color: var(--bg);">
                        @if(!empty($item['image']))
                            <img src="{{ $item['image'] }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-40 object-cover">
                        @endif
                        <div class="p-6">
                            @if(!empty($item['icon']))
                                <div class="text-4xl mb-4" style="color: var(--primary);">{!! $item['icon'] !!}</div>
                            @endif
                            <h3 class="text-xl font-semibold mb-2" style="font-family: var(--font-heading);">{{ $item['title'] ?? '' }}</h3>
                            <p class="opacity-80">{{ $item['description'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
