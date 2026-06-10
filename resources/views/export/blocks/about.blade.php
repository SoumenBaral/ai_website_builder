@php $imgPos = $props['imagePosition'] ?? 'right'; @endphp
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div class="{{ $imgPos === 'left' ? 'md:order-2' : '' }}">
            @if(!empty($props['heading']))
                <h2 class="text-3xl font-bold mb-6" style="font-family: var(--font-heading);">
                    {{ $props['heading'] }}
                </h2>
            @endif
            @if(!empty($props['body']))
                <div class="leading-relaxed opacity-90">{!! $props['body'] !!}</div>
            @endif
        </div>
        <div class="{{ $imgPos === 'left' ? 'md:order-1' : '' }}">
            @if(!empty($props['image']))
                <img src="{{ $props['image'] }}" alt="{{ $props['heading'] ?? 'About' }}" class="w-full object-cover shadow-lg" style="border-radius: var(--radius);">
            @endif
        </div>
    </div>
</section>
