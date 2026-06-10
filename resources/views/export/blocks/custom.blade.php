@php
    $columns = $props['columns'] ?? 1;
    $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3', 4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'][$columns] ?? 'grid-cols-1';
    $gapClass = ['sm' => 'gap-2', 'md' => 'gap-4', 'lg' => 'gap-8', 'xl' => 'gap-12'][$props['gap'] ?? 'md'] ?? 'gap-4';
    $padClass = ['sm' => 'py-8 px-4', 'md' => 'py-12 px-6', 'lg' => 'py-16 px-6', 'xl' => 'py-24 px-8'][$props['padding'] ?? 'lg'] ?? 'py-16 px-6';

    $radiusMap = ['none'=>'0','sm'=>'0.125rem','md'=>'0.375rem','lg'=>'0.5rem','xl'=>'0.75rem','full'=>'9999px'];
    $shadowMap = ['sm'=>'0 1px 2px rgba(0,0,0,0.05)','md'=>'0 4px 6px rgba(0,0,0,0.1)','lg'=>'0 10px 15px rgba(0,0,0,0.1)','xl'=>'0 20px 25px rgba(0,0,0,0.1)'];
    $headingSizes = ['xl'=>'text-xl','2xl'=>'text-2xl','3xl'=>'text-3xl','4xl'=>'text-4xl'];
    $textSizes = ['sm'=>'text-sm','base'=>'text-base','lg'=>'text-lg','xl'=>'text-xl'];
    $iconSizes = ['2xl'=>'text-2xl','3xl'=>'text-3xl','4xl'=>'text-4xl','5xl'=>'text-5xl'];
    $spacerH = ['sm'=>'1rem','md'=>'2rem','lg'=>'4rem','xl'=>'6rem'];
    $alignMap = ['left'=>'text-left','center'=>'text-center','right'=>'text-right'];
@endphp
<section class="{{ $padClass }}" style="background-color: var(--bg); color: var(--text);">
    <div class="max-w-6xl mx-auto">
        <div class="grid {{ $gridClass }} {{ $gapClass }}">
            @foreach(($props['elements'] ?? []) as $el)
                @php $elType = $el['type'] ?? ''; @endphp

                @if($elType === 'heading')
                    <h2 class="{{ $headingSizes[$el['size'] ?? '2xl'] ?? 'text-2xl' }} {{ $alignMap[$el['align'] ?? 'left'] ?? '' }} font-bold" style="font-family: var(--font-heading);">
                        {{ $el['text'] ?? '' }}
                    </h2>

                @elseif($elType === 'text')
                    <p class="{{ $textSizes[$el['size'] ?? 'base'] ?? 'text-base' }} {{ $alignMap[$el['align'] ?? 'left'] ?? '' }} opacity-80 leading-relaxed" style="font-family: var(--font-body);">
                        {{ $el['text'] ?? '' }}
                    </p>

                @elseif($elType === 'image' && !empty($el['src']))
                    @php
                        $imgStyle = '';
                        if (!empty($el['radius']) && isset($radiusMap[$el['radius']])) $imgStyle .= 'border-radius:'.$radiusMap[$el['radius']].';';
                        if (!empty($el['shadow']) && isset($shadowMap[$el['shadow']])) $imgStyle .= 'box-shadow:'.$shadowMap[$el['shadow']].';';
                        $widthClass = ($el['width'] ?? 'full') === 'half' ? 'w-1/2' : (($el['width'] ?? 'full') === 'third' ? 'w-1/3' : 'w-full');
                    @endphp
                    <div class="{{ $alignMap[$el['align'] ?? 'center'] ?? 'text-center' }}">
                        <img src="{{ $el['src'] }}" alt="{{ $el['alt'] ?? '' }}" class="{{ $widthClass }} h-auto max-h-96 object-cover {{ !empty($el['border']) && $el['border'] === 'thin' ? 'border border-gray-200' : '' }} {{ !empty($el['border']) && $el['border'] === 'thick' ? 'border-2 border-gray-300' : '' }}" style="{{ $imgStyle }}">
                    </div>

                @elseif($elType === 'icon')
                    <div class="{{ $iconSizes[$el['size'] ?? '4xl'] ?? 'text-4xl' }} {{ $alignMap[$el['align'] ?? 'center'] ?? 'text-center' }}">
                        {!! $el['emoji'] ?? '' !!}
                    </div>

                @elseif($elType === 'button')
                    @php
                        $v = $el['variant'] ?? 'primary';
                        $btnClass = 'inline-block px-6 py-3 font-medium transition-opacity hover:opacity-90';
                        if ($v === 'outline') {
                            $btnStyle = 'border: 2px solid var(--primary); color: var(--primary); border-radius: var(--radius);';
                        } elseif ($v === 'secondary') {
                            $btnStyle = 'background-color: var(--secondary); color: white; border-radius: var(--radius);';
                        } else {
                            $btnStyle = 'background-color: var(--primary); color: white; border-radius: var(--radius);';
                        }
                    @endphp
                    <div class="{{ $alignMap[$el['align'] ?? 'center'] ?? 'text-center' }}">
                        <a href="{{ $el['href'] ?? '#' }}" class="{{ $btnClass }}" style="{{ $btnStyle }}">{{ $el['text'] ?? 'Button' }}</a>
                    </div>

                @elseif($elType === 'divider')
                    <hr class="col-span-full border-current opacity-20 {{ ($el['style'] ?? 'solid') === 'dashed' ? 'border-dashed' : (($el['style'] ?? 'solid') === 'dotted' ? 'border-dotted' : '') }}">

                @elseif($elType === 'spacer')
                    <div class="col-span-full" style="height: {{ $spacerH[$el['height'] ?? 'md'] ?? '2rem' }};"></div>
                @endif
            @endforeach
        </div>
    </div>
</section>
