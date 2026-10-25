@php
    $cols = $props['columns'] ?? 3;
    $align = $props['align'] ?? 'center';
    $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3', 4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'][$cols] ?? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
@endphp
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['image']))
            <img {!! \App\Support\SiteHtml::img($props['image'], '(min-width: 1280px) 1152px, 100vw') !!} alt="" class="w-full h-48 object-cover mb-10" style="border-radius: var(--radius);">
        @endif

        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold {{ $align === 'left' ? 'text-left' : 'text-center' }} mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif
        <div class="grid {{ $gridClass }} gap-8">
            @if(!empty($props['items']))
                @foreach($props['items'] as $i => $item)
                    @php $href = $links[$i] ?? null; @endphp
                    <{{ $href ? 'a' : 'div' }} @if($href) href="{{ \App\Support\SiteHtml::href($href) }}" aria-label="{{ $item['title'] ?? '' }} — view details" @endif class="group block overflow-hidden border {{ $align === 'left' ? 'text-left' : 'text-center' }} {{ $href ? 'transition-shadow hover:shadow-xl' : '' }}" style="border-radius: var(--radius); background-color: var(--surface); border-color: var(--line); color: inherit; text-decoration: none;">
                        @if(!empty($item['image']))
                            <img {!! \App\Support\SiteHtml::img($item['image'], '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', [480, 768, 1024]) !!} alt="{{ $item['title'] ?? '' }}" class="w-full h-40 object-cover">
                        @endif
                        <div class="p-6">
                            @if(!empty($item['icon']))
                                <div class="text-4xl mb-4" style="color: var(--primary);">{{ $item['icon'] }}</div>
                            @endif
                            <h3 class="text-xl font-semibold mb-2" style="font-family: var(--font-heading);">{{ $item['title'] ?? '' }}</h3>
                            <p class="opacity-80">{{ $item['description'] ?? '' }}</p>
                            @if($href)
                                @include('export.partials.view-details')
                            @endif
                        </div>
                    </{{ $href ? 'a' : 'div' }}>
                @endforeach
            @endif
        </div>
    </div>
</section>
