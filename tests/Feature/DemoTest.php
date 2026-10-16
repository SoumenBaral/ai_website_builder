<?php

use App\Models\Order;
use App\Services\DemoSite;
use App\Services\ItemPages;

it('shows the premium demo shop with every feature switched on', function () {
    $html = $this->get('/demo')->assertOk()->getContent();

    expect($html)->toContain('Maison Atelier')
        ->toContain('data-text-fx="typing-loop"')      // typing headline
        ->toContain('data-fx=')                        // background effects
        ->toContain('data-btn-fx="shine"')
        ->toContain('data-anim="custom"')
        ->toContain('href="' . route('demo.item', ['services', 'the-oxford-shirt']) . '"')
        ->toContain('"mode":"demo"')                   // cart in demo mode
        ->toContain('Build yours free');
});

it('serves a product page for every demo product', function () {
    foreach (ItemPages::map(DemoSite::schema()) as $entry) {
        foreach ($entry['slugs'] as $slug) {
            $this->get(route('demo.item', [$entry['anchor'], $slug]))->assertOk()->assertSee('data-add-to-cart data-id', false);
        }
    }
    $this->get('/demo/services/nothing-here')->assertNotFound();
    expect(Order::count())->toBe(0); // the demo never stores orders
});

it('keeps the demo copy and images in good shape', function () {
    $schema = app(\App\Services\Ai\SchemaNormalizer::class)->normalizeSite(DemoSite::schema(), ensureFrame: false);
    expect(count($schema['sections']))->toBe(count(DemoSite::schema()['sections']));
    // Every product has a price, a photo, details and highlights
    $shop = collect(DemoSite::schema()['sections'])->firstWhere('type', 'services')['props']['items'];
    foreach ($shop as $item) {
        expect($item)->toHaveKeys(['price', 'image', 'details', 'highlights']);
    }
});
