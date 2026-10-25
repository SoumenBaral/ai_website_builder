<?php

use App\Services\Ai\Providers\ProviderResolver;

function resolverConfig(array $overrides = []): array
{
    return array_replace_recursive([
        'provider' => 'auto',
        'api_key' => null,
        'providers' => [
            'openai' => ['api_key' => null, 'models' => ['generate' => 'gpt-4.1', 'edit' => 'gpt-4.1-mini']],
            'puku' => ['api_key' => null, 'base_url' => 'https://api-cli.puku.sh', 'models' => ['generate' => 'opus', 'edit' => 'puku-ai-2.8']],
        ],
    ], $overrides);
}

it('recognises provider keys by prefix', function () {
    expect(ProviderResolver::kindOf('pk_live_abc'))->toBe('puku')
        ->and(ProviderResolver::kindOf('pk_test_abc'))->toBe('puku')
        ->and(ProviderResolver::kindOf('sk-proj-abc'))->toBe('openai')
        ->and(ProviderResolver::kindOf(' sk-abc '))->toBe('openai')
        ->and(ProviderResolver::kindOf('something-else'))->toBeNull();
});

it('auto-detects Puku from AI_API_KEY', function () {
    $r = new ProviderResolver(resolverConfig(['api_key' => 'pk_live_123', 'providers' => ['openai' => ['api_key' => 'sk-openai']]]));

    expect($r->detect())->toBe(['provider' => 'puku', 'key' => 'pk_live_123', 'source' => 'AI_API_KEY']);
});

it('auto-detects OpenAI from AI_API_KEY', function () {
    $r = new ProviderResolver(resolverConfig(['api_key' => 'sk-abc', 'providers' => ['puku' => ['api_key' => 'pk_live_x']]]));

    expect($r->detect())->toBe(['provider' => 'openai', 'key' => 'sk-abc', 'source' => 'AI_API_KEY']);
});

it('still works when a Puku key is pasted into OPENAI_API_KEY', function () {
    $r = new ProviderResolver(resolverConfig(['providers' => ['openai' => ['api_key' => 'pk_live_oops']]]));

    expect($r->detect())->toBe(['provider' => 'puku', 'key' => 'pk_live_oops', 'source' => 'OPENAI_API_KEY']);
});

it('prefers OpenAI when both vendor keys are set and nothing is forced', function () {
    $r = new ProviderResolver(resolverConfig(['providers' => ['openai' => ['api_key' => 'sk-a'], 'puku' => ['api_key' => 'pk_live_b']]]));

    expect($r->detect()['provider'])->toBe('openai');
});

it('uses only PUKU_API_KEY when AI_PROVIDER=puku is forced', function () {
    $r = new ProviderResolver(resolverConfig(['provider' => 'puku', 'providers' => ['openai' => ['api_key' => 'sk-a'], 'puku' => ['api_key' => 'pk_live_b']]]));

    expect($r->detect())->toBe(['provider' => 'puku', 'key' => 'pk_live_b', 'source' => 'PUKU_API_KEY']);
    expect($r->detect('openai'))->toBe(['provider' => 'openai', 'key' => 'sk-a', 'source' => 'OPENAI_API_KEY']);
});

it('falls back to OpenAI with no key when nothing is configured', function () {
    expect((new ProviderResolver(resolverConfig()))->detect())->toBe(['provider' => 'openai', 'key' => null, 'source' => 'none']);
});

it('masks keys for display', function () {
    expect(ProviderResolver::mask('pk_live_tTXmmGxdEgpr0K0lLVi7JpXsOLMQT6zDhiylqNqUYu0'))->toBe('pk_live_…UYu0')
        ->and(ProviderResolver::mask(null))->toBe('(not set)')
        ->and(ProviderResolver::mask('short'))->toBe('sho…');
});
