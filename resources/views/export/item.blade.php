{{--
    A card's details page (product, service, menu item, team member…).
    Uses the site's own theme variables, so it matches light and dark sites.
--}}
@php
    $isTeam = $type === 'team';
    $price = $item['price'] ?? null;
    $firstName = $isTeam ? strtok($title, ' ') : null;
    $initial = mb_strtoupper(mb_substr($title, 0, 1));
@endphp
<style>
    .ip { --line: color-mix(in srgb, var(--text) 12%, transparent); --soft: color-mix(in srgb, var(--text) 4%, transparent); --muted: color-mix(in srgb, var(--text) 68%, transparent); }
    .ip a { text-decoration: none; }
    .ip-crumbs a:hover, .ip-link:hover { color: var(--primary); }
    .ip-thumb { border: 2px solid transparent; transition: border-color .2s, opacity .2s; opacity: .7; }
    .ip-thumb:hover { opacity: 1; }
    .ip-thumb[aria-current="true"] { border-color: var(--primary); opacity: 1; }
    .ip-main-img { transition: opacity .25s ease; }
    .ip-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .9rem 1.6rem; font-weight: 600; border-radius: var(--radius); transition: transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s, opacity .2s; }
    .ip-btn:hover { transform: translateY(-1px); }
    .ip-btn-primary { background: var(--primary); color: #fff; box-shadow: 0 10px 30px -12px var(--primary); }
    .ip-btn-ghost { border: 1px solid var(--line); color: var(--text); }
    .ip-btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
    .ip-card { border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s, border-color .3s; }
    .ip-card:hover { transform: translateY(-4px); border-color: color-mix(in srgb, var(--primary) 45%, transparent); box-shadow: 0 22px 44px -24px rgb(0 0 0 / .45); }
    .ip-card:hover .ip-card-img { transform: scale(1.04); }
    .ip-card-img { transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .ip-check { background: color-mix(in srgb, var(--primary) 14%, transparent); color: var(--primary); }
    .ip-bar { background: color-mix(in srgb, var(--bg) 88%, transparent); -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); border-top: 1px solid var(--line); }
    @media (max-width: 767px) { .ip { padding-bottom: 6rem; } }
</style>

<main class="ip" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto px-6 pt-8 md:pt-12 pb-16 md:pb-24">

        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="ip-crumbs mb-8 flex items-center gap-2 text-sm min-w-0" style="color: var(--muted);">
            <a href="{{ $homeUrl }}" class="shrink-0 transition-colors">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $sectionUrl }}" class="shrink-0 transition-colors">{{ $sectionHeading }}</a>
            <span aria-hidden="true">/</span>
            <span class="truncate" style="color: var(--text);" aria-current="page">{{ $title }}</span>
        </nav>

        <div data-anim="fade-up" data-anim-stagger>
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">

                {{-- Gallery --}}
                <div class="lg:sticky lg:top-24">
                    <figure class="relative overflow-hidden {{ $isTeam ? 'aspect-square' : 'aspect-[4/3]' }}" style="border-radius: var(--radius); background: var(--soft);">
                        @if($photos)
                            <img id="ip-main" src="{{ $photos[0] }}" alt="{{ $title }}" class="ip-main-img absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center" style="background: radial-gradient(circle at 30% 25%, color-mix(in srgb, var(--primary) 35%, transparent), transparent 60%), linear-gradient(135deg, color-mix(in srgb, var(--primary) 18%, var(--bg)), color-mix(in srgb, var(--secondary) 22%, var(--bg)));">
                                <span class="text-7xl md:text-8xl font-bold" style="font-family: var(--font-heading); color: var(--primary);">{{ $item['icon'] ?? $initial }}</span>
                            </div>
                        @endif
                    </figure>
                    @if(count($photos) > 1)
                        <div class="mt-4 grid grid-cols-5 gap-3" role="group" aria-label="Photos">
                            @foreach($photos as $i => $photo)
                                <button type="button" data-thumb="{{ $photo }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Show photo {{ $i + 1 }}" class="ip-thumb aspect-square overflow-hidden" style="border-radius: calc(var(--radius) * .75);">
                                    <img src="{{ $photo }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em]" style="color: var(--primary);">{{ $sectionHeading }}</p>
                    <h1 class="mt-3 text-4xl md:text-5xl font-bold leading-[1.1] break-words" style="font-family: var(--font-heading);">{{ $title }}</h1>
                    @if($isTeam && !empty($item['role']))
                        <p class="mt-3 text-lg font-medium" style="color: var(--primary);">{{ $item['role'] }}</p>
                    @endif
                    @if($price)
                        <p class="mt-5 text-3xl font-bold" style="font-family: var(--font-heading); color: var(--primary);">{{ $price }}</p>
                    @endif
                    @if($summary !== '' && !($isTeam && $summary === ($item['role'] ?? '')))
                        <p class="mt-5 text-lg leading-relaxed" style="color: var(--muted);">{{ $summary }}</p>
                    @endif

                    @if($product)
                        {{-- Shop: quantity · Add to cart · Buy now (shown once the cart script runs) --}}
                        <div class="shop-only sc-buy mt-8">
                            <div class="sc-stepper" role="group" aria-label="Quantity">
                                <button type="button" data-qty-step="{{ $product['id'] }}" data-delta="-1" aria-label="Decrease quantity">−</button>
                                <input type="number" min="1" max="99" value="1" inputmode="numeric" data-qty-for="{{ $product['id'] }}" aria-label="Quantity">
                                <button type="button" data-qty-step="{{ $product['id'] }}" data-delta="1" aria-label="Increase quantity">+</button>
                            </div>
                            <button type="button" class="ip-btn ip-btn-primary flex-1 min-w-[11rem]" data-add-to-cart data-id="{{ $product['id'] }}">
                                <span class="sc-add inline-flex items-center gap-2">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2"/><circle cx="10" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
                                    Add to cart
                                </span>
                                <span class="sc-added items-center gap-2">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                    Added
                                </span>
                            </button>
                            <button type="button" class="ip-btn ip-btn-ghost" data-buy-now data-id="{{ $product['id'] }}">Buy now</button>
                        </div>
                        <div class="shop-only mt-4 flex-wrap items-center gap-x-3 gap-y-1 text-sm" style="color: var(--muted);">
                            <a href="{{ $sectionUrl }}" class="ip-link inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap transition-colors">← Browse all</a>
                            <span aria-hidden="true">·</span>
                            <span>No online payment — the shop confirms your order with you.</span>
                        </div>
                    @endif

                    <div class="mt-8 flex flex-wrap gap-3 {{ $product ? 'shop-fallback' : '' }}">
                        <a href="{{ $cta['href'] }}" class="ip-btn ip-btn-primary">
                            {{ $cta['text'] }}
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <a href="{{ $sectionUrl }}" class="ip-btn ip-btn-ghost" title="Back to {{ $sectionHeading }}">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                            {{ $isTeam ? 'Meet the team' : 'Browse all' }}
                        </a>
                    </div>

                    @if($highlights)
                        <ul class="mt-10 space-y-3.5">
                            @foreach($highlights as $point)
                                <li class="flex items-start gap-3">
                                    <span class="ip-check mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                    </span>
                                    <span class="leading-relaxed">{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($prev && $next)
                        <div class="mt-10 pt-6 flex items-center justify-between gap-4 text-sm" style="border-top: 1px solid var(--line); color: var(--muted);">
                            <a href="{{ $prev['url'] }}" class="ip-link flex min-w-0 items-center gap-2 transition-colors" rel="prev">
                                <span aria-hidden="true">←</span><span class="truncate">{{ $prev['title'] }}</span>
                            </a>
                            <span class="shrink-0 tabular-nums">{{ $position }} / {{ $count }}</span>
                            <a href="{{ $next['url'] }}" class="ip-link flex min-w-0 items-center justify-end gap-2 transition-colors" rel="next">
                                <span class="truncate">{{ $next['title'] }}</span><span aria-hidden="true">→</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Full description --}}
        @if($paragraphs)
            <div data-anim="fade-up">
                <section class="mt-20 md:mt-28 max-w-3xl">
                    <h2 class="text-2xl md:text-3xl font-bold" style="font-family: var(--font-heading);">{{ $isTeam ? "About {$firstName}" : 'Details' }}</h2>
                    <div class="mt-6 space-y-5 text-lg leading-relaxed" style="color: var(--muted);">
                        @foreach($paragraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </section>
            </div>
        @endif

        {{-- More from this section --}}
        @if($related)
            <div data-anim="fade-up" data-anim-stagger>
                <section class="mt-20 md:mt-28">
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="text-2xl md:text-3xl font-bold" style="font-family: var(--font-heading);">{{ $isTeam ? 'Meet the rest of the team' : 'You may also like' }}</h2>
                        <a href="{{ $sectionUrl }}" class="ip-link shrink-0 text-sm font-semibold transition-colors" style="color: var(--primary);">View all →</a>
                    </div>
                    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($related as $r)
                            @php $rTitle = \App\Services\ItemPages::title($r['item']); @endphp
                            <a href="{{ $r['url'] }}" class="ip-card group block" style="background: var(--bg); color: var(--text);">
                                <div class="overflow-hidden {{ $isTeam ? 'aspect-square' : 'aspect-[4/3]' }}" style="background: var(--soft);">
                                    @if(!empty($r['item']['image']))
                                        <img src="{{ $r['item']['image'] }}" alt="{{ $rTitle }}" class="ip-card-img w-full h-full object-cover" loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-5xl font-bold" style="color: var(--primary); font-family: var(--font-heading);">{{ $r['item']['icon'] ?? mb_strtoupper(mb_substr($rTitle, 0, 1)) }}</div>
                                    @endif
                                </div>
                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="text-lg font-semibold leading-snug" style="font-family: var(--font-heading);">{{ $rTitle }}</h3>
                                        @if(!empty($r['item']['price']))
                                            <span class="shrink-0 font-bold" style="color: var(--primary);">{{ $r['item']['price'] }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($r['item']['description'] ?? $r['item']['role'] ?? null))
                                        <p class="mt-1.5 text-sm line-clamp-2" style="color: var(--muted);">{{ $r['item']['description'] ?? $r['item']['role'] }}</p>
                                    @endif
                                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold" style="color: var(--primary);">View details <span aria-hidden="true" class="transition-transform group-hover:translate-x-1">→</span></span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        @endif
    </div>

    {{-- Sticky buy bar on phones --}}
    <div class="ip-bar fixed inset-x-0 bottom-0 z-40 md:hidden px-4 py-3" style="padding-bottom: max(.75rem, env(safe-area-inset-bottom));">
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold" style="font-family: var(--font-heading);">{{ $title }}</p>
                @if($price)
                    <p class="text-sm font-bold" style="color: var(--primary);">{{ $price }}</p>
                @elseif($isTeam && !empty($item['role']))
                    <p class="truncate text-xs" style="color: var(--muted);">{{ $item['role'] }}</p>
                @endif
            </div>
            @if($product)
                <button type="button" class="shop-only ip-btn ip-btn-primary !py-3 !px-5 text-sm shrink-0" data-add-to-cart data-id="{{ $product['id'] }}">
                    <span class="sc-add">Add to cart</span><span class="sc-added items-center gap-1.5">✓ Added</span>
                </button>
            @endif
            <a href="{{ $cta['href'] }}" class="{{ $product ? 'shop-fallback' : '' }} ip-btn ip-btn-primary !py-3 !px-5 text-sm shrink-0">{{ $cta['text'] }}</a>
        </div>
    </div>
</main>

@if(count($photos) > 1)
<script>
document.addEventListener('click', function (e) {
    var thumb = e.target.closest('[data-thumb]');
    if (!thumb) return;
    var main = document.getElementById('ip-main');
    if (!main || main.getAttribute('src') === thumb.getAttribute('data-thumb')) return;
    main.style.opacity = '0';
    setTimeout(function () {
        main.src = thumb.getAttribute('data-thumb');
        main.onload = function () { main.style.opacity = '1'; };
        setTimeout(function () { main.style.opacity = '1'; }, 700); // cached images may not fire load
    }, 160);
    document.querySelectorAll('[data-thumb]').forEach(function (t) { t.setAttribute('aria-current', t === thumb ? 'true' : 'false'); });
});
</script>
@endif
