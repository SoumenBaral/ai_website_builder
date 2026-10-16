<?php

use App\Models\User;
use App\Services\Ai\ImageLibrary;
use Illuminate\Support\Facades\Http;

it('searches the built-in photo library', function () {
    config(['ai.unsplash_access_key' => null]);
    Http::fake();
    $user = User::factory()->create();

    $res = $this->actingAs($user)->getJson(route('stock.index', ['q' => 'sneakers']))->assertOk();
    $ids = collect($res->json('photos'))->pluck('id');
    expect($ids)->not->toBeEmpty()
        ->and($ids->every(fn ($id) => str_contains(implode(' ', ImageLibrary::PHOTOS[$id]), 'sneaker') || str_contains(implode(' ', ImageLibrary::PHOTOS[$id]), 'shoe')))->toBeTrue()
        ->and($res->json('photos.0.url'))->toStartWith('https://images.unsplash.com/photo-')
        ->and($res->json('categories'))->not->toBeEmpty();

    // Categories filter, people = portraits, nonsense = nothing
    $food = $this->actingAs($user)->getJson(route('stock.index', ['category' => 'food']))->json('photos');
    expect(count($food))->toBeGreaterThan(10);
    expect($this->actingAs($user)->getJson(route('stock.index', ['category' => 'people']))->json('photos'))->not->toBeEmpty();
    expect($this->actingAs($user)->getJson(route('stock.index', ['q' => 'zzqxv']))->json('photos'))->toBe([]);
    Http::assertNothingSent(); // no key, no live search
});

it('adds live Unsplash results when a key is configured', function () {
    config(['ai.unsplash_access_key' => 'test-key']);
    Http::fake(['api.unsplash.com/*' => Http::response(['results' => [[
        'id' => 'abc', 'alt_description' => 'red sneaker', 'urls' => ['raw' => 'https://images.unsplash.com/photo-abc?ixid=1', 'small' => 'https://images.unsplash.com/photo-abc?w=400'], 'user' => ['name' => 'Jane Doe'],
    ]]])]);

    $photos = $this->actingAs(User::factory()->create())->getJson(route('stock.index', ['q' => 'red sneaker']))->assertOk()->json('photos');
    expect($photos[0])->toMatchArray(['id' => 'u-abc', 'credit' => 'Jane Doe']);
});

it('requires login', function () {
    $this->getJson(route('stock.index'))->assertUnauthorized();
});
