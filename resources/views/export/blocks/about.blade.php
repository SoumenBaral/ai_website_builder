@php $imgPos = $props['imagePosition'] ?? 'right'; @endphp
{{-- Mirrors resources/js/Components/builder/blocks/BlockAbout.vue: keep the two in step. --}}
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div class="{{ $imgPos === 'left' ? 'md:order-2' : '' }}">
            @if(!empty($props['heading']))
                <h2 class="text-3xl md:text-4xl font-bold mb-6" style="font-family: var(--font-heading);">
                    {{ $props['heading'] }}
                </h2>
            @endif
            @if(!empty($props['body']))
                <div class="text-lg leading-relaxed opacity-80 space-y-[1.625em]">{!! \App\Support\SiteHtml::paragraphs($props['body']) !!}</div>
            @endif
        </div>
        <div class="{{ $imgPos === 'left' ? 'md:order-1' : '' }}">
            @if(!empty($props['image']))
                <img {!! \App\Support\SiteHtml::img($props['image'], '(min-width: 768px) 50vw, 100vw') !!} alt="{{ $props['heading'] ?? 'About' }}" width="1200" height="900" class="w-full h-80 object-cover" style="{{ \App\Support\SiteHtml::imageStyle($props) ?: 'border-radius: var(--radius); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);' }}">
            @endif
        </div>
    </div>
</section>
