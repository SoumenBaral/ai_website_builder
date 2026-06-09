<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @php $columns = $props['columns'] ?? 3; @endphp

        <div class="grid gap-4" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
            @if(!empty($props['images']))
                @foreach($props['images'] as $image)
                    <div class="overflow-hidden" style="border-radius: var(--radius);">
                        <img src="{{ $image['src'] ?? '' }}" alt="{{ $image['caption'] ?? '' }}" class="w-full h-64 object-cover">
                        @if(!empty($image['caption']))
                            <p class="text-sm mt-2 opacity-70 text-center">{{ $image['caption'] }}</p>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
