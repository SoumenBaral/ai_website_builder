<?php

use App\Services\Ai\ImageLibrary;
use App\Services\ImageResolver;

uses(Tests\TestCase::class);

function photoId(string $url): string
{
    preg_match('#photo-([\w-]+)\?#', $url, $m);

    return $m[1] ?? '';
}

beforeEach(fn () => $this->images = new ImageResolver());

it('picks photos that match the query', function () {
    $out = $this->images->resolvePlaceholders(['sections' => [
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:bride and groom at wedding ceremony']],
        ['type' => 'about', 'props' => ['image' => 'PLACEHOLDER:latte art coffee cup']],
        ['type' => 'features', 'props' => ['items' => [['image' => 'PLACEHOLDER:dentist with patient']]]],
    ]]);

    expect(ImageLibrary::PHOTOS[photoId($out['sections'][0]['props']['image'])])->toContain('wedding')
        ->and(ImageLibrary::PHOTOS[photoId($out['sections'][1]['props']['image'])])->toContain('coffee')
        ->and(ImageLibrary::PHOTOS[photoId($out['sections'][2]['props']['items'][0]['image'])])->toContain('dental');
});

it('does not repeat photos within a site while good matches remain', function () {
    $images = array_map(fn ($i) => ['src' => "PLACEHOLDER:wedding photo {$i}"], range(1, 4));
    $out = $this->images->resolvePlaceholders(['sections' => [['type' => 'gallery', 'props' => ['images' => $images]]]]);
    $ids = array_map(fn ($img) => photoId($img['src']), $out['sections'][0]['props']['images']);

    expect(count(array_unique($ids)))->toBe(4);
});

it('treats photos already on the page as used', function () {
    $existing = 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=900';
    $out = $this->images->resolvePlaceholders(['sections' => [
        ['type' => 'about', 'props' => ['image' => $existing]],
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:coffee latte']],
    ]]);

    expect(photoId($out['sections'][1]['props']['image']))->not->toBe('1495474472287-4d71bcdd2085');
});

it('uses square face-cropped portraits for people and respects gender words', function () {
    $out = $this->images->resolvePlaceholders(['sections' => [['type' => 'team', 'props' => ['members' => [
        ['image' => 'PLACEHOLDER:portrait of smiling woman'],
        ['image' => 'PLACEHOLDER:portrait of a man in a suit'],
    ]]]]]);
    [$woman, $man] = array_column($out['sections'][0]['props']['members'], 'image');

    expect($woman)->toContain('crop=faces')
        ->and(ImageLibrary::PORTRAITS[photoId($woman)])->toBe('woman')
        ->and(ImageLibrary::PORTRAITS[photoId($man)])->toBe('man');
});

it('uses wide images for full-section backgrounds and the site context for vague queries', function () {
    $out = $this->images->resolvePlaceholders(['sections' => [
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:welcoming atmosphere']],
    ]], 'italian restaurant pasta');
    $url = $out['sections'][0]['props']['image'];

    expect($url)->toContain('w=1600')
        ->and(array_intersect(ImageLibrary::PHOTOS[photoId($url)], ['restaurant', 'italian', 'pasta']))->not->toBeEmpty();
});

it('drops optional decorative images rather than using an unrelated photo', function () {
    $out = $this->images->resolvePlaceholders(['sections' => [
        ['type' => 'faq', 'props' => ['image' => 'PLACEHOLDER:qwzx unrelated words']],
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:qwzx unrelated words']],
        ['type' => 'features', 'props' => ['items' => [['title' => 'A', 'image' => 'PLACEHOLDER:qwzx']]]],
    ]]);

    expect($out['sections'][0]['props'])->not->toHaveKey('image')         // optional: dropped
        ->and($out['sections'][1]['props']['image'])->toStartWith('https://') // required: always filled
        ->and($out['sections'][2]['props']['items'][0])->not->toHaveKey('image');
});

it('stays on topic for the business when matching photos run out', function () {
    $dental = fn ($q) => ['image' => "PLACEHOLDER:{$q}"];
    $out = $this->images->resolvePlaceholders(['sections' => [
        ['type' => 'hero', 'props' => $dental('dentist with patient')],
        ['type' => 'services', 'props' => ['items' => [$dental('dental checkup'), $dental('teeth whitening'), $dental('dental x-ray'), $dental('doctor consultation clinic')]]],
        ['type' => 'about', 'props' => $dental('modern dental clinic interior')],
        ['type' => 'faq', 'props' => $dental('dental clinic waiting area')],
    ]], 'dental clinic smilecare');

    $about = ImageLibrary::PHOTOS[photoId($out['sections'][2]['props']['image'])];
    expect(array_intersect($about, ['dental', 'dentist', 'clinic', 'doctor', 'healthcare']))->not->toBeEmpty()
        ;
    // Optional image: either a fresh on-topic photo, or none at all — never an off-topic one.
    if (isset($out['sections'][3]['props']['image'])) {
        expect(array_intersect(ImageLibrary::PHOTOS[photoId($out['sections'][3]['props']['image'])], ['dental', 'dentist', 'clinic', 'doctor', 'healthcare', 'medical', 'hospital']))->not->toBeEmpty();
    }
});
