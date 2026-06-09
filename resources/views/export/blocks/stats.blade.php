<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="flex flex-wrap justify-center gap-12">
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
