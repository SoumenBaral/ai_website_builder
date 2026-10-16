<?php

use App\Models\User;
use App\Services\Ai\ImageLibrary;
use OpenAI\Laravel\Facades\OpenAI;

function photo_tags(?string $url): array
{
    return $url && preg_match('#photo-([\w-]+)\?#', $url, $m) ? (ImageLibrary::PHOTOS[$m[1]] ?? []) : [];
}

it('finds matching, non-repeating photos for card titles without using AI', function () {
    OpenAI::fake([]); // any AI call would fail this test
    $sneakers = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=900';

    $urls = $this->actingAs(User::factory()->create())->postJson(route('ai.photos'), [
        'queries' => ['Oxford Shirt A crisp staple', 'Lightweight Jacket', 'Slim Chino Pant', 'Minimalist Sneakers'],
        'hint' => 'Forma Men menswear clothing store',
        'exclude' => [$sneakers], // already on the page
    ])->assertOk()->json('urls');

    expect(photo_tags($urls[0]))->toContain('shirt')
        ->and(photo_tags($urls[1]))->toContain('jacket')
        ->and(photo_tags($urls[2]))->toContain('chinos')
        ->and($urls[3])->not->toContain('1542291026-7eec264c27ff')   // never repeats a photo on the page
        ->and(count(array_unique(array_filter($urls))))->toBe(count(array_filter($urls)));
    OpenAI::assertNothingSent();
});

it('requires a signed-in user', function () {
    $this->postJson(route('ai.photos'), ['queries' => ['shirt']])->assertUnauthorized();
});

it('shows prices on published service cards', function () {
    $html = app(\App\Services\ExportService::class)->toHtml([
        'meta' => ['title' => 'Store', 'description' => '', 'language' => 'en'],
        'theme' => ['primaryColor' => '#111111', 'secondaryColor' => '#000000', 'backgroundColor' => '#ffffff', 'textColor' => '#111111', 'fontHeading' => 'Inter', 'fontBody' => 'Inter', 'radius' => 'md', 'mode' => 'light'],
        'sections' => [['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop', 'items' => [['title' => 'Oxford Shirt', 'description' => 'Crisp', 'price' => '$45', 'image' => 'https://images.unsplash.com/photo-1620012253295-c15cc3e65df4?w=900']]]]],
    ]);

    expect($html)->toContain('$45')->toContain('photo-1620012253295-c15cc3e65df4');
});
