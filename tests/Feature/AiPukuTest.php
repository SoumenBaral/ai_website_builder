<?php

use App\Models\AiGeneration;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\Providers\AnthropicSchema;
use App\Services\Ai\Providers\ChatProvider;
use App\Services\Ai\Providers\ChatRequest;
use App\Services\Ai\Providers\ProviderException;
use App\Services\Ai\Providers\PukuProvider;
use App\Services\AiWebsiteService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const PUKU_URL = 'https://api-cli.puku.sh/v1/messages';

/** A full-site response shaped like the strict schema (same fixture as the OpenAI test). */
function pukuSite(): array
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

/** An Anthropic Messages JSON response. */
function pukuMessage(array|string $content, string $stop = 'end_turn'): array
{
    return [
        'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => 'opus',
        'content' => [['type' => 'text', 'text' => is_array($content) ? json_encode($content) : $content]],
        'stop_reason' => $stop,
        'usage' => ['input_tokens' => 3000, 'output_tokens' => 2500],
    ];
}

/** An Anthropic SSE stream delivering the JSON in small chunks. */
function pukuStream(array $content, string $stop = 'end_turn'): string
{
    $ev = fn (string $type, array $data) => "event: {$type}\ndata: " . json_encode(['type' => $type] + $data) . "\n\n";
    $out = $ev('message_start', ['message' => ['id' => 'msg_1', 'usage' => ['input_tokens' => 3000, 'output_tokens' => 1]]]);
    $out .= $ev('content_block_start', ['index' => 0, 'content_block' => ['type' => 'text', 'text' => '']]);
    foreach (str_split(json_encode($content), 40) as $chunk) {
        $out .= $ev('content_block_delta', ['index' => 0, 'delta' => ['type' => 'text_delta', 'text' => $chunk]]);
    }
    $out .= $ev('content_block_stop', ['index' => 0]);
    $out .= $ev('message_delta', ['delta' => ['stop_reason' => $stop, 'stop_sequence' => null], 'usage' => ['output_tokens' => 2500]]);
    $out .= $ev('message_stop', []);

    return $out;
}

function pukuError(int $status, string $code, string $message): \GuzzleHttp\Promise\PromiseInterface
{
    return Http::response(['error' => ['type' => 'error', 'code' => $code, 'message' => $message]], $status);
}

function pukuProvider(): PukuProvider
{
    return new PukuProvider('pk_live_test', ['generate' => 'opus', 'edit' => 'puku-ai-2.8']);
}

function healthRequest(): ChatRequest
{
    return new ChatRequest(
        model: 'opus',
        messages: [['role' => 'system', 'content' => 'Be terse.'], ['role' => 'user', 'content' => 'ping']],
        schemaName: 'health',
        schema: ['type' => 'object', 'properties' => ['ok' => ['type' => 'boolean']], 'required' => ['ok'], 'additionalProperties' => false],
        maxTokens: 100,
        temperature: 0.2,
    );
}

/* ---------------------------------------------------------------------
 | PukuProvider: wire format
 * --------------------------------------------------------------------- */

it('sends an Anthropic Messages request with the pk_live key and structured output', function () {
    Http::fake([PUKU_URL => Http::response(pukuMessage(['ok' => true]))]);

    $result = pukuProvider()->complete(healthRequest());

    expect($result->content)->toBe('{"ok":true}')
        ->and($result->finish)->toBe('stop')
        ->and($result->usage)->toBe(['input' => 3000, 'output' => 2500]);

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $request->url() === PUKU_URL
            && $request->hasHeader('x-api-key', 'pk_live_test')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && !$request->hasHeader('authorization')
            && $body['model'] === 'opus'
            && $body['max_tokens'] === 100
            && $body['temperature'] === 0.2
            && $body['system'] === 'Be terse.'                       // system prompt lifted out of messages
            && $body['messages'] === [['role' => 'user', 'content' => 'ping']]
            && $body['output_config']['format']['type'] === 'json_schema'
            && $body['output_config']['format']['schema']['additionalProperties'] === false
            && !isset($body['stream']);
    });
});

it('parses a streamed response and reports deltas as they arrive', function () {
    Http::fake([PUKU_URL => Http::response(pukuStream(['ok' => true, 'note' => str_repeat('x', 100)]))]);

    $deltas = [];
    $result = pukuProvider()->stream(healthRequest(), function (string $d) use (&$deltas) {
        $deltas[] = $d;
    });

    expect(count($deltas))->toBeGreaterThan(2)
        ->and(implode('', $deltas))->toBe($result->content)
        ->and(json_decode($result->content, true)['ok'])->toBeTrue()
        ->and($result->finish)->toBe('stop')
        ->and($result->usage)->toBe(['input' => 3000, 'output' => 2500]);

    Http::assertSent(fn (Request $r) => $r->data()['stream'] === true);
});

it('reports truncation as "length" so the service can retry', function () {
    Http::fake([PUKU_URL => Http::response(pukuMessage('{"ok":', 'max_tokens'))]);

    expect(pukuProvider()->complete(healthRequest())->truncated())->toBeTrue();
});

it('falls back to the beta output_format, then to prompt-only JSON, when the gateway rejects structured outputs', function () {
    Http::fake([PUKU_URL => Http::sequence()
        ->push(['error' => ['type' => 'invalid_request_error', 'message' => 'output_config: Extra inputs are not permitted']], 400)
        ->push(['error' => ['type' => 'invalid_request_error', 'message' => 'output_format: Extra inputs are not permitted']], 400)
        ->push(pukuMessage('"ok":true}')),
    ]);

    $result = pukuProvider()->complete(healthRequest());

    expect($result->content)->toBe('{"ok":true}'); // the "{" prefill is re-attached

    $sent = collect(Http::recorded())->map(fn ($pair) => $pair[0]);
    expect($sent)->toHaveCount(3)
        ->and($sent[0]->data())->toHaveKey('output_config')
        ->and($sent[1]->data())->toHaveKey('output_format')
        ->and($sent[1]->hasHeader('anthropic-beta', 'structured-outputs-2025-11-13'))->toBeTrue()
        ->and($sent[2]->data())->not->toHaveKey('output_config')
        ->and($sent[2]->data())->not->toHaveKey('output_format')
        ->and($sent[2]->data()['messages'][1])->toBe(['role' => 'assistant', 'content' => '{'])
        ->and($sent[2]->data()['system'])->toContain('JSON schema');
});

it('classifies Puku errors so the caller knows whether to retry', function (int $status, array $error, string $kind) {
    Http::fake([PUKU_URL => Http::response(['error' => $error], $status)]);

    try {
        pukuProvider()->complete(healthRequest());
        $this->fail('expected a ProviderException');
    } catch (ProviderException $e) {
        expect($e->kind)->toBe($kind)->and($e->status)->toBe($status)->and($e->getMessage())->toContain((string) $status);
    }
    Http::assertSentCount(1); // never retried inside the provider
})->with([
    'plan quota (what puku.sh returns on a free account)' => [429, ['type' => 'quota_exceeded', 'code' => 'chat_req_limit_exceeded', 'message' => 'Monthly chat request limit reached (0/0). Upgrade your plan for more.'], 'quota'],
    'plain rate limit' => [429, ['type' => 'rate_limit_error', 'message' => 'Too many requests'], 'rate_limited'],
    'bad key' => [401, ['type' => 'authentication_error', 'code' => 'invalid_token', 'message' => 'Invalid or expired token.'], 'rejected'],
    'unknown model' => [400, ['type' => 'invalid_request_error', 'message' => 'model: not found'], 'rejected'],
    'gateway down' => [502, ['type' => 'api_error', 'message' => 'upstream unavailable'], 'transport'],
]);

it('treats network failures as transport errors', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host'));

    expect(fn () => pukuProvider()->complete(healthRequest()))
        ->toThrow(fn (ProviderException $e) => $e->kind === 'transport');
});

it('strips schema keywords Anthropic does not accept', function () {
    $schema = [
        'type' => 'object', 'additionalProperties' => false, 'required' => ['n', 'tags', 'child'],
        'properties' => [
            'n' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100, 'description' => 'kept'],
            'tags' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 9, 'items' => ['type' => 'string', 'maxLength' => 20, 'enum' => ['a', 'b']]],
            'child' => ['anyOf' => [['type' => 'null'], ['type' => 'string', 'minLength' => 1]]],
        ],
    ];

    $clean = AnthropicSchema::sanitize($schema);

    expect($clean['properties']['n'])->toBe(['type' => 'integer', 'description' => 'kept'])
        ->and($clean['properties']['tags'])->toBe(['type' => 'array', 'minItems' => 1, 'items' => ['type' => 'string', 'enum' => ['a', 'b']]])
        ->and($clean['properties']['child']['anyOf'][1])->toBe(['type' => 'string'])
        ->and($clean['required'])->toBe(['n', 'tags', 'child']);
});

/* ---------------------------------------------------------------------
 | End to end: the app running on Puku
 * --------------------------------------------------------------------- */

describe('website generation on Puku', function () {
    beforeEach(function () {
        config(['ai.provider' => 'puku', 'ai.providers.puku.api_key' => 'pk_live_test']);
        app()->forgetInstance(ChatProvider::class);

        $this->user = User::factory()->create(['plan' => 'free']);
        $this->project = Project::create(['user_id' => $this->user->id, 'name' => 'Lumen', 'slug' => 'lumen-puku01', 'status' => 'draft']);
        $this->page = Page::create(['project_id' => $this->project->id, 'title' => 'Home', 'path' => '/', 'is_home' => true, 'schema' => [
            'version' => 1, 'meta' => ['title' => 'Lumen', 'description' => '', 'language' => 'en'],
            'theme' => ['primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a', 'backgroundColor' => '#ffffff', 'textColor' => '#0f172a', 'fontHeading' => 'Poppins', 'fontBody' => 'Inter', 'radius' => 'lg', 'mode' => 'light'],
            'sections' => [],
        ]]);
    });

    it('resolves the Puku provider with the opus model', function () {
        $provider = app(AiWebsiteService::class)->provider();

        expect($provider)->toBeInstanceOf(PukuProvider::class)
            ->and($provider->model('generate'))->toBe('opus')
            ->and($provider->model('edit'))->toBe('puku-ai-2.8');
    });

    it('generates, repairs and saves a website', function () {
        Http::fake([PUKU_URL => Http::response(pukuMessage(pukuSite()))]);

        $response = $this->actingAs($this->user)->postJson(route('ai.generate'), ['prompt' => 'Wedding photographer in Lisbon', 'project_id' => $this->project->id]);

        $response->assertOk();
        $schema = $response->json('schema');

        expect(array_column($schema['sections'], 'type'))->toBe(['navbar', 'hero', 'gallery', 'contact', 'footer'])
            ->and($schema['sections'][1]['props']['image'])->toStartWith('https://images.unsplash.com/');
        expect($this->page->fresh()->schema['sections'])->toHaveCount(5);
        expect(AiGeneration::first())->model->toBe('opus')->output_tokens->toBe(2500);

        Http::assertSent(fn (Request $r) => $r->data()['model'] === 'opus'
            && str_contains($r->data()['system'], 'conversion copywriter')
            && $r->data()['output_config']['format']['schema']['properties']['sections']['type'] === 'array');
    });

    it('streams progress events and finishes with the website', function () {
        Http::fake([PUKU_URL => Http::response(pukuStream(pukuSite()))]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['Accept' => 'application/x-ndjson'])
            ->post(route('ai.generate'), ['prompt' => 'Wedding photographer in Lisbon', 'project_id' => $this->project->id]);

        $response->assertOk();
        $events = array_map(fn ($line) => json_decode($line, true), array_filter(explode("\n", $response->streamedContent())));
        $types = array_column($events, 'event');

        expect($types[0])->toBe('progress')
            ->and(end($types))->toBe('done')
            ->and(collect($events)->pluck('message')->filter()->implode(' '))->toContain('section')
            ->and(end($events)['schema']['sections'])->toHaveCount(5);
    });

    it('retries once when Puku truncates, then succeeds', function () {
        Http::fake([PUKU_URL => Http::sequence()->push(pukuMessage('{"plan":', 'max_tokens'))->push(pukuMessage(pukuSite()))]);

        $this->actingAs($this->user)
            ->postJson(route('ai.generate'), ['prompt' => 'A bakery', 'project_id' => $this->project->id])
            ->assertOk()
            ->assertJsonPath('schema.sections.0.type', 'navbar');
        Http::assertSentCount(2);
    });

    it('explains an exhausted Puku plan instead of a generic error', function () {
        Http::fake([PUKU_URL => pukuError(429, 'chat_req_limit_exceeded', 'Monthly chat request limit reached (0/0). Upgrade your plan for more.')]);

        $response = $this->actingAs($this->user)->postJson(route('ai.generate'), ['prompt' => 'A bakery', 'project_id' => $this->project->id]);

        $response->assertStatus(503);
        expect($response->json('error'))->toContain('Puku')->toContain('plan quota');
        expect(AiGeneration::count())->toBe(0);
        Http::assertSentCount(1); // quota errors are not retried
    });

    it('edits a section through Puku with the edit model', function () {
        Http::fake([PUKU_URL => Http::response(pukuMessage([
            'summary' => 'Changed the headline.',
            'props' => ['heading' => 'New headline', 'items' => [
                ['icon' => '📸', 'title' => 'Unposed', 'description' => 'Real moments.', 'image' => null],
            ], 'columns' => 3, 'align' => 'center', 'image' => null, 'imageRadius' => null, 'imageShadow' => null, 'imageBorder' => null, 'sectionBg' => null, 'sectionText' => null],
        ]))]);

        $response = $this->actingAs($this->user)->postJson(route('ai.editSection'), [
            'section' => ['id' => 'blk_features_42', 'type' => 'features', 'props' => ['heading' => 'Why us', 'items' => []]],
            'theme' => $this->page->schema['theme'],
            'instruction' => 'Change the headline to New headline',
        ]);

        $response->assertOk()
            ->assertJsonPath('summary', 'Changed the headline.')
            ->assertJsonPath('section.id', 'blk_features_42')
            ->assertJsonPath('section.props.heading', 'New headline');
        Http::assertSent(fn (Request $r) => $r->data()['model'] === 'puku-ai-2.8');
    });
});
