<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @if(!empty($props['items']))
                @foreach($props['items'] as $item)
                    <div class="p-6 shadow-md" style="border-radius: var(--radius); background-color: var(--bg); border: 1px solid rgba(0,0,0,0.1);">
                        @if(!empty($item['icon']))
                            <div class="text-4xl mb-4" style="color: var(--primary);">
                                {!! $item['icon'] !!}
                            </div>
                        @endif

                        <h3 class="text-xl font-semibold mb-2" style="font-family: var(--font-heading);">
                            {{ $item['title'] ?? '' }}
                        </h3>

                        <p class="opacity-80">
                            {{ $item['description'] ?? '' }}
                        </p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
