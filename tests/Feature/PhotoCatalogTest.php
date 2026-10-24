<?php

use App\Models\StockPhoto;
use App\Models\User;
use App\Services\Ai\Providers\ChatProvider;
use App\Services\Ai\Providers\ChatRequest;
use App\Services\Ai\Providers\ChatResult;
use App\Services\ImageResolver;
use App\Services\Photos\PhotoAnalyzer;
use App\Services\Photos\PhotoCatalog;
use Illuminate\Support\Facades\Http;

/* ------------------------------------------------------------------ helpers */

function pexelsPhoto(int $id, string $alt, int $w = 4000, int $h = 2600): array
{
    return [
        'id' => $id, 'width' => $w, 'height' => $h, 'alt' => $alt, 'avg_color' => '#8a6d4b',
        'photographer' => 'Pat Lee', 'photographer_url' => 'https://www.pexels.com/@pat',
        'src' => ['original' => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg", 'medium' => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?h=350", 'small' => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?h=130"],
    ];
}

/** A described catalog photo, as photos:analyze would leave it. */
function catalogPhoto(int $id, string $category, string $topic, array $subjects, array $tags, string $description, array $extra = []): StockPhoto
{
    $p = new StockPhoto(array_merge([
        'source' => 'pexels', 'source_id' => (string) $id,
        'url' => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg",
        'thumb_url' => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?h=350",
        'width' => 4000, 'height' => 2600, 'orientation' => 'landscape',
        'category' => $category, 'topic' => $topic, 'alt' => $description, 'photographer' => 'Pat Lee',
        'ai_description' => $description, 'ai_subjects' => $subjects, 'ai_tags' => $tags,
        'people' => false, 'person' => null, 'mood' => 'calm', 'placements' => ['hero', 'card'],
        'analyzed_at' => now(),
    ], $extra));
    $p->search_text = $p->buildSearchText();
    $p->save();

    return $p;
}

/** 60 varied photos so the catalog counts as "ready", plus the specific ones a test needs. */
function seedCatalog(): void
{
    PhotoCatalog::forget();
    catalogPhoto(1, 'health', 'dentist clinic', ['dentist', 'patient'], ['dentist', 'dental', 'clinic', 'patient', 'smile', 'healthcare', 'teeth', 'chair'], 'A dentist in a white coat smiles at a patient sitting in a dental chair in a bright clinic.', ['people' => true]);
    catalogPhoto(2, 'health', 'doctor with patient', ['doctor', 'patient'], ['doctor', 'patient', 'hospital', 'healthcare', 'stethoscope', 'consultation', 'medical'], 'A doctor with a stethoscope talks to a patient at a hospital desk.', ['people' => true]);
    catalogPhoto(3, 'food', 'coffee latte art', ['latte', 'coffee'], ['latte', 'coffee', 'cafe', 'cup', 'latte art', 'wooden table', 'barista', 'drink'], 'A cup of latte with leaf-shaped latte art on a wooden cafe table.');
    catalogPhoto(4, 'food', 'cafe interior cozy', ['cafe'], ['cafe', 'interior', 'coffee shop', 'tables', 'cozy', 'restaurant', 'wood', 'plants'], 'A cozy cafe interior with wooden tables, plants and warm lights.');
    catalogPhoto(5, 'events', 'wedding couple', ['bride', 'groom'], ['wedding', 'bride', 'groom', 'couple', 'ceremony', 'flowers', 'dress', 'celebration'], 'A bride and groom hold hands during an outdoor wedding ceremony with flowers.', ['people' => true]);
    catalogPhoto(6, 'people', 'smiling woman portrait', ['woman'], ['woman', 'portrait', 'smile', 'face', 'headshot', 'professional', 'natural light'], 'Head-and-shoulders portrait of a smiling woman in natural light.', ['people' => true, 'person' => 'woman', 'orientation' => 'portrait', 'placements' => ['portrait']]);
    catalogPhoto(7, 'people', 'smiling man portrait', ['man'], ['man', 'portrait', 'smile', 'face', 'headshot', 'beard', 'professional'], 'Head-and-shoulders portrait of a smiling man with a beard.', ['people' => true, 'person' => 'man', 'orientation' => 'portrait', 'placements' => ['portrait']]);
    catalogPhoto(8, 'home', 'living room interior', ['living room', 'sofa'], ['living room', 'sofa', 'interior', 'home', 'apartment', 'decor', 'cushions', 'window'], 'A bright living room with a grey sofa, cushions and a large window.');
    catalogPhoto(9, 'food', 'coffee beans', ['coffee bean'], ['coffee', 'coffee bean', 'roast', 'sack', 'roaster', 'brown', 'cafe'], 'Roasted coffee beans spilling from a burlap sack.');
    // Filler so the catalog is "ready"
    foreach (range(100, 160) as $i) {
        catalogPhoto($i, 'nature', 'mountain landscape sunrise', ['mountain'], ['mountain', 'landscape', 'sunrise', 'nature', 'peak', 'sky', 'outdoors', "filler{$i}"], "Mountain peaks at sunrise under a clear sky, scene {$i}.");
    }
    PhotoCatalog::forget();
}

/** A ChatProvider that answers every request with a fixed JSON payload (or throws). */
function fakeProvider(array|\Closure $answer): ChatProvider
{
    return new class($answer) implements ChatProvider
    {
        public array $requests = [];

        public function __construct(private array|\Closure $answer) {}

        public function name(): string { return 'fake'; }
        public function label(): string { return 'Fake'; }
        public function model(string $purpose): string { return 'fake-model'; }
        public function complete(ChatRequest $request): ChatResult
        {
            $this->requests[] = $request;
            $answer = $this->answer instanceof \Closure ? ($this->answer)($request) : $this->answer;

            return new ChatResult(json_encode($answer), 'stop', ['input' => 10, 'output' => 10]);
        }
        public function stream(ChatRequest $request, callable $onDelta): ChatResult { return $this->complete($request); }
    };
}

beforeEach(fn () => PhotoCatalog::forget());

/* ------------------------------------------------------------------ import */

it('imports photos from Pexels into the catalog, verifying and de-duplicating', function () {
    config(['ai.photos.pexels_key' => 'px-test']);
    Http::fake([
        'api.pexels.com/v1/search*' => function ($request) {
            $q = $request['query'];

            return Http::response([
                'total_results' => 2,
                'photos' => [pexelsPhoto(crc32($q) % 100000, "{$q} photo one"), pexelsPhoto(99, 'shared photo'), pexelsPhoto(7, 'tall photo', 2000, 3000)],
            ], 200, ['X-Ratelimit-Remaining' => '150']);
        },
        'images.pexels.com/photos/7/*' => Http::response('', 404),   // broken → skipped
        'images.pexels.com/*' => Http::response('', 200),
    ]);

    $this->artisan('photos:import', ['--topic' => ['coffee latte art', 'dentist clinic'], '--per-topic' => 3])
        ->assertSuccessful();

    expect(StockPhoto::count())->toBe(3) // 2 unique topic photos + photo 99 once
        ->and(StockPhoto::where('source_id', '7')->exists())->toBeFalse()
        ->and(StockPhoto::where('source_id', '99')->first()->topic)->toBe('coffee latte art');

    $coffee = StockPhoto::where('topic', 'coffee latte art')->first();
    expect($coffee->category)->toBe('food')
        ->and($coffee->orientation)->toBe('landscape')
        ->and($coffee->photographer)->toBe('Pat Lee')
        ->and($coffee->search_text)->toContain('coffee latte art');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.pexels.com') && $r->hasHeader('Authorization', 'px-test'));

    // Re-running does not duplicate and does not call the API for full topics.
    $this->artisan('photos:import', ['--topic' => ['coffee latte art'], '--per-topic' => 2])->assertSuccessful();
    expect(StockPhoto::count())->toBe(3);
});

it('refuses to import without a Pexels key', function () {
    config(['ai.photos.pexels_key' => null]);
    $this->artisan('photos:import')->assertFailed();
});

/* ------------------------------------------------------------------ analyze */

it('describes photos with the vision model and stores validated results', function () {
    $a = catalogPhoto(1, 'food', 'coffee latte art', [], [], 'latte', ['analyzed_at' => null, 'ai_description' => null, 'ai_tags' => null, 'ai_subjects' => null]);
    $b = catalogPhoto(2, 'health', 'dentist clinic', [], [], 'dentist', ['analyzed_at' => null, 'ai_description' => null, 'ai_tags' => null, 'ai_subjects' => null]);

    $provider = fakeProvider(['photos' => [
        ['number' => 1, 'description' => 'A cup of latte with leaf latte art on a wooden table.', 'subjects' => ['Latte', 'coffee'], 'tags' => ['latte', 'coffee', 'cafe', 'Latte Art!', 'wooden table', 'cup', 'drink', 'barista'], 'people' => false, 'person' => null, 'mood' => 'Cozy', 'placements' => ['card', 'gallery', 'bogus']],
        ['number' => 2, 'description' => 'A dentist smiles at a patient in a dental chair.', 'subjects' => ['dentist', 'patient'], 'tags' => ['dentist', 'dental', 'clinic', 'patient', 'healthcare'], 'people' => true, 'person' => 'woman', 'mood' => 'friendly', 'placements' => ['hero', 'card']],
    ]]);

    $saved = (new PhotoAnalyzer($provider))->analyze(collect([$a, $b]), 'vision-model');

    expect($saved)->toBe(2);
    $a->refresh();
    $b->refresh();
    expect($a->ai_subjects)->toBe(['latte', 'coffee'])
        ->and($a->ai_tags)->toContain('latte art')->not->toContain('Latte Art!')
        ->and($a->placements)->toBe(['card', 'gallery'])
        ->and($a->mood)->toBe('cozy')
        ->and($a->people)->toBeFalse()
        ->and($a->analyzed_at)->not->toBeNull()
        ->and($a->search_text)->toContain('wooden table')
        ->and($b->person)->toBe('woman')
        ->and($b->people)->toBeTrue();

    // The request carried both thumbnails as low-detail images, in order.
    $content = $provider->requests[0]->messages[1]['content'];
    $images = array_values(array_filter($content, fn ($part) => $part['type'] === 'image_url'));
    expect($images)->toHaveCount(2)
        ->and($images[0]['image_url']['url'])->toBe($a->thumb_url)
        ->and($images[0]['image_url']['detail'])->toBe('low')
        ->and($provider->requests[0]->model)->toBe('vision-model');
});

it('ignores implausible analysis rows instead of saving junk', function () {
    $a = catalogPhoto(1, 'food', 'coffee latte art', [], [], 'latte', ['analyzed_at' => null]);
    $provider = fakeProvider(['photos' => [['number' => 1, 'description' => 'x', 'subjects' => [], 'tags' => ['a'], 'people' => false, 'person' => null, 'mood' => '', 'placements' => []]]]);

    expect((new PhotoAnalyzer($provider))->analyze(collect([$a])))->toBe(0)
        ->and($a->fresh()->analyzed_at)->toBeNull();
});

it('runs photos:analyze over pending photos only, resumably', function () {
    catalogPhoto(1, 'food', 'coffee latte art', [], [], 'latte', ['analyzed_at' => null]);
    catalogPhoto(2, 'food', 'cafe interior cozy', [], [], 'cafe', ['analyzed_at' => null]);
    catalogPhoto(3, 'food', 'bakery bread croissant', ['bread'], ['bread'], 'already done'); // analyzed

    $provider = fakeProvider(fn (ChatRequest $r) => ['photos' => array_map(fn ($i) => [
        'number' => $i, 'description' => "Described photo number {$i} in detail.", 'subjects' => ['thing'], 'tags' => ['thing', 'object', 'scene'], 'people' => false, 'person' => null, 'mood' => 'calm', 'placements' => ['card'],
    ], range(1, count(array_filter($r->messages[1]['content'], fn ($p) => $p['type'] === 'image_url'))))]);
    config(['ai.photos.analyze_provider' => null]);
    // The command asks the container's ProviderResolver for a provider; hand it the fake.
    $this->app->instance(\App\Services\Ai\Providers\ProviderResolver::class, new class($provider) extends \App\Services\Ai\Providers\ProviderResolver {
        public function __construct(private ChatProvider $p) { parent::__construct([]); }
        public function resolve(?string $force = null): ChatProvider { return $this->p; }
    });

    $this->artisan('photos:analyze')->assertSuccessful();

    expect(StockPhoto::whereNull('analyzed_at')->count())->toBe(0)
        ->and(StockPhoto::find(3)->ai_description)->toBe('already done') // untouched
        ->and(count($provider->requests))->toBe(1);                      // both pending photos in one batch
});

/* ------------------------------------------------------------------ search */

it('ranks catalog photos by subject, then tags, then description words', function () {
    seedCatalog();
    $catalog = app(PhotoCatalog::class);

    $top = fn (string $q, string $placement = 'card', string $hint = '') => $catalog->search($q, $hint, $placement)[0]['photo']['source_id'] ?? null;

    expect($top('dentist smiling with patient'))->toBe('1')
        ->and($top('latte art on wooden table'))->toBe('3')
        ->and($top('cozy coffee shop interior'))->toBe('4')
        ->and($top('bride and groom wedding ceremony', 'wide'))->toBe('5')
        ->and($top('roasted coffee beans'))->toBe('9')
        ->and($catalog->search('qwzx nonsense words'))->toBe([]);
});

it('uses the business context to break ties and prefers the right shape per placement', function () {
    seedCatalog();
    $catalog = app(PhotoCatalog::class);

    // "patient" matches both the dentist and the doctor equally; the business decides.
    expect($catalog->search('smiling patient', 'dental clinic smilecare', 'card')[0]['photo']['source_id'])->toBe('1')
        ->and($catalog->search('smiling patient', 'city hospital', 'card')[0]['photo']['source_id'])->toBe('2');

    // Portraits only come from photos of people, matching the asked gender.
    expect($catalog->search('portrait of smiling woman', '', 'portrait')[0]['photo']['source_id'])->toBe('6')
        ->and($catalog->search('portrait of a man in a suit', '', 'portrait')[0]['photo']['source_id'])->toBe('7')
        ->and(collect($catalog->search('smiling person', '', 'portrait'))->pluck('photo.people')->unique()->all())->toBe([true]);
});

/* ------------------------------------------------------------------ resolver */

it('fills placeholders from the catalog with placement-sized Pexels URLs and never repeats', function () {
    seedCatalog();
    $resolver = new ImageResolver(app(PhotoCatalog::class), fakeProvider(['choice' => 1, 'reason' => 'best']));

    $out = $resolver->resolvePlaceholders(['sections' => [
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:dentist smiling with patient']],
        ['type' => 'about', 'props' => ['image' => 'PLACEHOLDER:dentist with patient in clinic']],
        ['type' => 'team', 'props' => ['members' => [['image' => 'PLACEHOLDER:portrait of smiling woman'], ['image' => 'PLACEHOLDER:portrait of a man']]]],
        ['type' => 'gallery', 'props' => ['images' => [['src' => 'PLACEHOLDER:latte art'], ['src' => 'PLACEHOLDER:latte art coffee']]]],
    ]], 'dental clinic');

    $hero = $out['sections'][0]['props']['image'];
    $about = $out['sections'][1]['props']['image'];
    expect($hero)->toStartWith('https://images.pexels.com/photos/1/')->toContain('w=1600')
        ->and($about)->toStartWith('https://images.pexels.com/photos/2/') // dentist already used → the doctor, still on topic
        ->and($out['sections'][2]['props']['members'][0]['image'])->toContain('/photos/6/')->toContain('w=400&h=400')
        ->and($out['sections'][2]['props']['members'][1]['image'])->toContain('/photos/7/');

    $gallery = array_column($out['sections'][3]['props']['images'], 'src');
    expect($gallery[0])->toContain('/photos/3/')->toContain('w=900&h=900')
        ->and($gallery[1])->not->toBe($gallery[0]);
});

it('treats catalog photos already on the page as used', function () {
    seedCatalog();
    $resolver = new ImageResolver(app(PhotoCatalog::class), fakeProvider(['choice' => 1, 'reason' => '']));

    $out = $resolver->resolvePlaceholders(['sections' => [
        ['type' => 'about', 'props' => ['image' => 'https://images.pexels.com/photos/3/pexels-photo-3.jpeg?w=900']],
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:latte art coffee cup']],
    ]]);

    expect($out['sections'][1]['props']['image'])->not->toContain('/photos/3/');
});

it('drops optional images with no relevant catalog photo and falls back for required ones', function () {
    seedCatalog();
    $resolver = new ImageResolver(app(PhotoCatalog::class), fakeProvider(['choice' => 1, 'reason' => '']));

    $out = $resolver->resolvePlaceholders(['sections' => [
        ['type' => 'faq', 'props' => ['image' => 'PLACEHOLDER:qwzx unrelated words']],
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:qwzx unrelated words']],
        ['type' => 'features', 'props' => ['items' => [['title' => 'A', 'image' => 'PLACEHOLDER:qwzx']]]],
    ]]);

    expect($out['sections'][0]['props'])->not->toHaveKey('image')
        ->and($out['sections'][1]['props']['image'])->toStartWith('https://images.unsplash.com/') // curated library fallback
        ->and($out['sections'][2]['props']['items'][0])->not->toHaveKey('image');
});

it('lets the AI choose the hero photo among close candidates, using their descriptions', function () {
    seedCatalog();
    config(['ai.photos.rerank' => true]);
    $provider = fakeProvider(['choice' => 2, 'reason' => 'second is better']);
    $resolver = new ImageResolver(app(PhotoCatalog::class), $provider);

    // "coffee" is close between latte (3), cafe interior (4) and beans (9).
    $out = $resolver->resolvePlaceholders(['sections' => [
        ['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:coffee cafe']],
        ['type' => 'services', 'props' => ['items' => [['image' => 'PLACEHOLDER:coffee cafe']]]],
    ]], 'coffee roaster');

    expect($provider->requests)->toHaveCount(1); // hero only — cards never spend a call
    $req = $provider->requests[0];
    expect($req->messages[1]['content'])->toContain('Request: coffee cafe')->toContain('Candidates:')->toContain('latte');

    // The AI's #2 pick was used (not the top-scored #1), and the card got a different photo.
    $candidates = app(PhotoCatalog::class)->search('coffee cafe', 'coffee roaster', 'wide', [], 8);
    $second = $candidates[1]['photo']['source_id'];
    expect($out['sections'][0]['props']['image'])->toContain("/photos/{$second}/")
        ->and($out['sections'][1]['props']['items'][0]['image'])->not->toContain("/photos/{$second}/");
});

it('keeps the top-scored photo when the rerank call fails', function () {
    seedCatalog();
    $provider = fakeProvider(fn () => throw new \App\Services\Ai\Providers\ProviderException('transport', 'boom'));
    $resolver = new ImageResolver(app(PhotoCatalog::class), $provider);

    $out = $resolver->resolvePlaceholders(['sections' => [['type' => 'hero', 'props' => ['image' => 'PLACEHOLDER:coffee cafe']]]]);
    $top = app(PhotoCatalog::class)->search('coffee cafe', '', 'wide')[0]['photo']['source_id'];

    expect($out['sections'][0]['props']['image'])->toContain("/photos/{$top}/");
});

/* ------------------------------------------------------------------ picker */

it('serves the catalog in the editor photo picker with search and categories', function () {
    seedCatalog();
    $user = User::factory()->create();

    $res = $this->actingAs($user)->getJson(route('stock.index', ['q' => 'dentist']))->assertOk();
    expect($res->json('catalog'))->toBe(70)
        ->and($res->json('photos.0.id'))->toBe('pexels:1')
        ->and($res->json('photos.0.thumb'))->toContain('images.pexels.com')->toContain('w=400&h=300')
        ->and($res->json('photos.0.credit'))->toBe('Pat Lee')
        ->and(collect($res->json('categories'))->firstWhere('key', 'food'))->toMatchArray(['label' => 'Food & drink', 'count' => 3]);

    $food = $this->actingAs($user)->getJson(route('stock.index', ['category' => 'food']))->json('photos');
    expect(collect($food)->pluck('id')->all())->toBe(['pexels:3', 'pexels:4', 'pexels:9']);

    $people = $this->actingAs($user)->getJson(route('stock.index', ['category' => 'people']))->json('photos');
    expect(collect($people)->pluck('id')->all())->toBe(['pexels:6', 'pexels:7']);
});
