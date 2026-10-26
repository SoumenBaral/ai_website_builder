{{-- Mirrors resources/js/Components/builder/blocks/BlockGallery.vue: keep the two in step. --}}
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @php $columns = $props['columns'] ?? 3; @endphp

        <div class="grid gap-4 grid-cols-1 {{ $columns >= 2 ? 'sm:grid-cols-2' : '' }} {{ $columns >= 3 ? 'lg:grid-cols-3' : '' }} {{ $columns >= 4 ? 'lg:grid-cols-4' : '' }}">
            @if(!empty($props['images']))
                @foreach($props['images'] as $image)
                    <div class="group relative overflow-hidden" style="border-radius: var(--radius);">
                        <img {!! \App\Support\SiteHtml::img($image['src'] ?? '', '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', [480, 768, 1024]) !!} alt="{{ $image['caption'] ?? '' }}" class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-105">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-end">
                            @if(!empty($image['caption']))
                                <p class="p-4 text-white text-sm opacity-0 group-hover:opacity-100 transition-opacity">{{ $image['caption'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
