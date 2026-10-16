<?php

use App\Models\AiGeneration;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Chat\CreateStreamedResponse;

/** A model response shaped like the strict schema (nullable props present, some deliberate flaws). */
function aiSite(): array
{
    return [
        'plan' => ['brand' => 'Lumen Studio', 'industry' => 'wedding photography', 'audience' => 'engaged couples', 'goal' => 'Check availability', 'tone' => 'warm, calm, honest', 'visualDirection' => 'Soft neutrals, Playfair headings'],
        'meta' => ['title' => 'Lumen Studio — Wedding Photography', 'description' => 'Natural wedding photography in Lisbon.', 'language' => 'en'],
        'theme' => ['primaryColor' => '#b45309', 'secondaryColor' => '#1c1917', 'backgroundColor' => '#fafaf9', 'textColor' => '#1c1917', 'fontHeading' => 'Playfair Display', 'fontBody' => 'Inter', 'radius' => 'md', 'mode' => 'light'],
        'sections' => [
            ['id' => 'blk_navbar_1', 'type' => 'navbar', 'props' => ['brand' => 'Lumen Studio', 'links' => [['label' => 'Work', 'href' => '#portfolio'], ['label' => 'Contact', 'href' => '#contact']], 'ctaText' => 'Check availability', 'ctaHref' => '#contact', 'sectionBg' => null, 'sectionText' => null]],
            ['id' => 'blk_hero_1', 'type' => 'hero', 'props' => ['heading' => 'Moments, beautifully kept', 'subheading' => 'Natural, unposed wedding photography.', 'ctaText' => 'Check availability', 'ctaHref' => '#contact', 'image' => 'PLACEHOLDER:bride and groom wedding ceremony', 'layout' => 'center', 'align' => null, 'overlayOpacity' => 20, 'minHeight' => 'full', 'sectionBg' => null, 'sectionText' => null]],
            ['id' => 'blk_gallery_1', 'type' => 'gallery', 'props' => ['heading' => 'Recent weddings', 'images' => [['src' => 'PLACEHOLDER:wedding bouquet', 'caption' => null], ['src' => 'PLACEHOLDER:wedding rings', 'caption' => 'Rings']], 'columns' => 3, 'sectionBg' => null, 'sectionText' => null]],
            ['id' => 'blk_contact_1', 'type' => 'contact', 'props' => ['heading' => "Let's plan your day", 'email' => 'hello@lumenstudio.com', 'phone' => null, 'address' => 'Lisbon, Portugal', 'fields' => ['Name', 'Email', 'Date', 'Message'], 'layout' => 'split', 'image' => null, 'imageRadius' => null, 'imageShadow' => null, 'imageBorder' => null, 'sectionBg' => null, 'sectionText' => null]],
        ],
    ];
}

function chatResponse(array $content, string $finish = 'stop'): CreateResponse
{
    return CreateResponse::fake([
        'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => json_encode($content)], 'finish_reason' => $finish]],
        'usage' => ['prompt_tokens' => 3000, 'completion_tokens' => 2500, 'total_tokens' => 5500],
    ]);
}

/** SSE stream that delivers the JSON in small chunks, like the real API. */
function streamedResponse(array $content)
{
    $json = json_encode($content);
    $lines = '';
    foreach (str_split($json, 40) as $chunk) {
        $lines .= 'data: ' . json_encode(['id' => 'c1', 'object' => 'chat.completion.chunk', 'created' => 1, 'model' => 'gpt-4.1', 'choices' => [['index' => 0, 'delta' => ['content' => $chunk], 'finish_reason' => null]]]) . "\n";
    }
    $lines .= 'data: ' . json_encode(['id' => 'c1', 'object' => 'chat.completion.chunk', 'created' => 1, 'model' => 'gpt-4.1', 'choices' => [['index' => 0, 'delta' => [], 'finish_reason' => 'stop']]]) . "\n";
    $lines .= 'data: ' . json_encode(['id' => 'c1', 'object' => 'chat.completion.chunk', 'created' => 1, 'model' => 'gpt-4.1', 'choices' => [], 'usage' => ['prompt_tokens' => 3000, 'completion_tokens' => 2500, 'total_tokens' => 5500]]) . "\n";
    $lines .= "data: [DONE]\n";

    $resource = fopen('php://memory', 'r+');
    fwrite($resource, $lines);
    rewind($resource);

    return CreateStreamedResponse::fake($resource);
}

beforeEach(function () {
    $this->user = User::factory()->create(['plan' => 'free']);
    $this->project = Project::create(['user_id' => $this->user->id, 'name' => 'Lumen', 'slug' => 'lumen-abc123', 'status' => 'draft']);
    $this->page = Page::create(['project_id' => $this->project->id, 'title' => 'Home', 'path' => '/', 'is_home' => true, 'schema' => [
        'version' => 1, 'meta' => ['title' => 'Lumen', 'description' => '', 'language' => 'en'],
        'theme' => ['primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#ffffff', 'textColor' => '#0f172a', 'fontHeading' => 'Poppins', 'fontBody' => 'Inter', 'radius' => 'lg', 'mode' => 'light'],
        'sections' => [],
    ]]);
});

it('generates, repairs and saves a website', function () {
    OpenAI::fake([chatResponse(aiSite())]);

    $response = $this->actingAs($this->user)->postJson(route('ai.generate'), ['prompt' => 'Wedding photographer in Lisbon', 'project_id' => $this->project->id]);

    $response->assertOk();
    $schema = $response->json('schema');

    expect($schema)->not->toHaveKey('plan')
        ->and(array_column($schema['sections'], 'type'))->toBe(['navbar', 'hero', 'gallery', 'contact', 'footer'])
        ->and($schema['sections'][0]['props']['links'][0]['href'])->toBe('#gallery')   // "#portfolio" → real section
        ->and($schema['sections'][1]['props']['overlayOpacity'])->toBe(45)             // readable hero text
        ->and($schema['sections'][1]['props']['image'])->toStartWith('https://images.unsplash.com/')
        ->and($schema['sections'][2]['props']['images'][0])->not->toHaveKey('caption') // nulls dropped
        ->and($schema['sections'][3]['props'])->not->toHaveKey('phone');

    expect($this->page->fresh()->schema['sections'])->toHaveCount(5);
    expect(PageVersion::count())->toBe(0); // empty page: nothing worth versioning
    expect(AiGeneration::first())->model->toBe('gpt-4.1')->output_tokens->toBe(2500);

    OpenAI::assertSent(\OpenAI\Resources\Chat::class, function (string $method, array $params) {
        return $params['response_format']['json_schema']['strict'] === true
            && $params['model'] === 'gpt-4.1'
            && str_contains($params['messages'][0]['content'], 'conversion copywriter');
    });
});

it('streams progress events and finishes with the website', function () {
    OpenAI::fake([streamedResponse(aiSite())]);

    $response = $this->actingAs($this->user)
        ->withHeaders(['Accept' => 'application/x-ndjson'])
        ->post(route('ai.generate'), ['prompt' => 'Wedding photographer in Lisbon', 'project_id' => $this->project->id]);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/x-ndjson');

    $events = array_map(fn ($line) => json_decode($line, true), array_filter(explode("\n", $response->streamedContent())));
    $types = array_column($events, 'event');

    expect($types[0])->toBe('progress')
        ->and(end($types))->toBe('done')
        ->and(collect($events)->pluck('message')->filter()->implode(' '))->toContain('section')
        ->and(end($events)['schema']['sections'])->toHaveCount(5);
});

it('retries once when the output is truncated, then explains the failure', function () {
    OpenAI::fake([chatResponse(['plan' => []], 'length'), chatResponse(['plan' => []], 'length')]);

    $response = $this->actingAs($this->user)->postJson(route('ai.generate'), ['prompt' => 'A bakery', 'project_id' => $this->project->id]);

    $response->assertStatus(502);
    expect($response->json('error'))->toContain('try again');
    expect(AiGeneration::count())->toBe(0); // failed attempts do not use up the quota
});

it('recovers when the retry succeeds', function () {
    OpenAI::fake([chatResponse(['plan' => []], 'length'), chatResponse(aiSite())]);

    $this->actingAs($this->user)
        ->postJson(route('ai.generate'), ['prompt' => 'A bakery', 'project_id' => $this->project->id])
        ->assertOk()
        ->assertJsonPath('schema.sections.0.type', 'navbar');
});

it('returns 429 with a friendly message when the monthly limit is reached', function () {
    foreach (range(1, 10) as $i) {
        AiGeneration::create(['user_id' => $this->user->id, 'prompt' => "site {$i}", 'model' => 'gpt-4.1', 'status' => 'completed']);
    }
    OpenAI::fake([]);

    $this->actingAs($this->user)
        ->postJson(route('ai.generate'), ['prompt' => 'A bakery'])
        ->assertStatus(429)
        ->assertJsonPath('error', fn ($msg) => str_contains($msg, '10 AI generations'));
});

it("refuses to overwrite someone else's project", function () {
    $other = User::factory()->create();
    OpenAI::fake([chatResponse(aiSite())]);

    $this->actingAs($other)
        ->postJson(route('ai.generate'), ['prompt' => 'Take over', 'project_id' => $this->project->id])
        ->assertForbidden();

    expect($this->page->fresh()->schema['sections'])->toBe([]);
});

it('edits one section, keeps its id and returns a summary', function () {
    OpenAI::fake([chatResponse([
        'summary' => 'Added two more benefits and tightened the copy.',
        'props' => ['heading' => 'Why couples choose us', 'items' => [
            ['icon' => '📸', 'title' => 'Unposed', 'description' => 'Real moments.', 'image' => null],
            ['icon' => '🕊️', 'title' => 'Calm', 'description' => 'No stress.', 'image' => null],
        ], 'columns' => 3, 'align' => 'center', 'image' => null, 'imageRadius' => null, 'imageShadow' => null, 'imageBorder' => null, 'sectionBg' => null, 'sectionText' => null],
    ])]);

    $response = $this->actingAs($this->user)->postJson(route('ai.editSection'), [
        'section' => ['id' => 'blk_features_42', 'type' => 'features', 'props' => ['heading' => 'Why us', 'items' => []]],
        'theme' => $this->page->schema['theme'],
        'instruction' => 'Add more benefits',
    ]);

    $response->assertOk()
        ->assertJsonPath('summary', 'Added two more benefits and tightened the copy.')
        ->assertJsonPath('section.id', 'blk_features_42')
        ->assertJsonPath('section.type', 'features')
        ->assertJsonPath('section.props.columns', 2);

    OpenAI::assertSent(\OpenAI\Resources\Chat::class, fn ($m, $p) => $p['response_format']['json_schema']['schema']['properties']['props']['properties']['items']['items']['required'] === ['icon', 'title', 'description', 'image']
        && $p['model'] === 'gpt-4.1-mini');
});

it('edits the whole site and keeps section ids', function () {
    $edited = aiSite();
    unset($edited['plan']);
    $edited = ['summary' => 'Translated the site to Portuguese.'] + $edited;
    OpenAI::fake([chatResponse($edited)]);

    $response = $this->actingAs($this->user)->postJson(route('ai.edit'), [
        'schema' => aiSite(),
        'instruction' => 'Translate to Portuguese',
    ]);

    $response->assertOk()
        ->assertJsonPath('summary', 'Translated the site to Portuguese.')
        ->assertJsonPath('schema.sections.1.id', 'blk_hero_1');
    // Edits never add a footer the user may have removed on purpose.
    expect(array_column($response->json('schema.sections'), 'type'))->not->toContain('footer');
});

it('adds section anchors to published pages so menu links work', function () {
    $schema = app(\App\Services\Ai\SchemaNormalizer::class)->normalizeSite(aiSite(), ensureFrame: true);
    $html = app(\App\Services\ExportService::class)->toHtml($schema);

    expect($html)->toContain('id="home"')->toContain('id="gallery"')->toContain('id="contact"')
        ->and($html)->toContain('href="#gallery"');
});
