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
                <div class="leading-relaxed opacity-90 space-y-4">{!! \App\Support\SiteHtml::paragraphs($props['body']) !!}</div>
            @endif
        </div>
        <div class="{{ $imgPos === 'left' ? 'md:order-1' : '' }}">
            @if(!empty($props['image']))
                <img {!! \App\Support\SiteHtml::img($props['image'], '(min-width: 768px) 50vw, 100vw') !!} alt="{{ $props['heading'] ?? 'About' }}" width="1200" height="900" class="w-full h-auto object-cover shadow-lg" style="border-radius: var(--radius); aspect-ratio: 4 / 3;">
            @endif
        </div>
    </div>
</section>
