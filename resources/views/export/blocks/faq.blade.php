<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    @php $wClass = ['sm' => 'max-w-xl', 'md' => 'max-w-3xl', 'lg' => 'max-w-5xl', 'full' => 'max-w-6xl'][$props['maxWidth'] ?? 'md'] ?? 'max-w-3xl'; @endphp
    <div class="{{ $wClass }} mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        <div class="divide-y" style="border-color: rgba(0,0,0,0.1);">
            @if(!empty($props['items']))
                @foreach($props['items'] as $item)
                    <div class="py-6" style="border-color: rgba(0,0,0,0.1);">
                        <h3 class="text-lg font-bold mb-2" style="font-family: var(--font-heading);">
                            {{ $item['question'] ?? '' }}
                        </h3>
                        <p class="opacity-80">
                            {{ $item['answer'] ?? '' }}
                        </p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
