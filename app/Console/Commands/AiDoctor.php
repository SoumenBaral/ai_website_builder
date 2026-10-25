<?php

namespace App\Console\Commands;

use App\Services\Ai\Providers\ChatRequest;
use App\Services\Ai\Providers\ProviderException;
use App\Services\Ai\Providers\ProviderResolver;
use Illuminate\Console\Command;

/**
 * Shows which AI provider the app will use and, unless --no-ping, makes one tiny
 * real request so a bad key, exhausted plan or wrong model shows up here instead
 * of in a user's browser.
 */
class AiDoctor extends Command
{
    protected $signature = 'ai:doctor
        {--provider= : Check a specific provider (openai|puku) instead of the configured one}
        {--no-ping : Only show the configuration, do not call the API}';

    protected $description = 'Show the resolved AI provider / models and verify the API key with a live request';

    public function handle(): int
    {
        $resolver = ProviderResolver::fromConfig();
        $force = $this->option('provider') ? strtolower($this->option('provider')) : null;
        if ($force && !in_array($force, ProviderResolver::PROVIDERS, true)) {
            $this->error('Unknown provider. Use one of: ' . implode(', ', ProviderResolver::PROVIDERS));

            return self::INVALID;
        }

        $detected = $resolver->detect($force);
        $provider = $resolver->resolve($force);

        $this->table(['Setting', 'Value'], [
            ['AI_PROVIDER', config('ai.provider', 'auto') . ($force ? " (overridden: {$force})" : '')],
            ['Resolved provider', $provider->label() . " ({$provider->name()})"],
            ['API key', ProviderResolver::mask($detected['key']) . " from {$detected['source']}"],
            ['Model · generate', $provider->model('generate')],
            ['Model · edit', $provider->model('edit')],
            ['Endpoint', $provider->name() === 'puku' ? config('ai.providers.puku.base_url') : (config('openai.base_uri') ?: 'https://api.openai.com/v1')],
        ]);

        if (!$detected['key']) {
            $this->error('No API key found. Set AI_API_KEY (any provider), OPENAI_API_KEY or PUKU_API_KEY in .env.');

            return self::FAILURE;
        }
        if ($this->option('no-ping')) {
            return self::SUCCESS;
        }

        $this->line('Pinging ' . $provider->label() . ' with a one-word structured request…');
        $request = new ChatRequest(
            model: $provider->model('edit'),
            messages: [
                ['role' => 'system', 'content' => 'You are a health check. Answer with JSON only.'],
                ['role' => 'user', 'content' => 'Set ok to true and say "pong" in message.'],
            ],
            schemaName: 'health',
            schema: [
                'type' => 'object',
                'properties' => ['ok' => ['type' => 'boolean'], 'message' => ['type' => 'string']],
                'required' => ['ok', 'message'],
                'additionalProperties' => false,
            ],
            maxTokens: 64,
            temperature: 0,
        );

        $started = microtime(true);
        try {
            $result = $provider->complete($request);
        } catch (ProviderException $e) {
            $this->error("✗ {$provider->label()} request failed ({$e->kind}): {$e->getMessage()}");
            $this->line(match ($e->kind) {
                ProviderException::QUOTA => '  The key is valid but the plan has no requests left — upgrade or top up the ' . $provider->label() . ' account.',
                ProviderException::REJECTED => '  Check the API key and model names in .env.',
                ProviderException::RATE_LIMITED => '  Try again in a moment.',
                default => '  Check network access to the endpoint above.',
            });

            return self::FAILURE;
        }

        $ms = (int) round((microtime(true) - $started) * 1000);
        $data = json_decode((string) $result->content, true);
        if (!is_array($data) || ($data['ok'] ?? null) !== true) {
            $this->error("✗ Reached {$provider->label()} but the reply was not the expected JSON: " . mb_strimwidth((string) $result->content, 0, 200, '…'));

            return self::FAILURE;
        }

        $this->info(sprintf('✓ %s replied "%s" in %dms (finish: %s, tokens in/out: %s/%s)',
            $provider->label(), $data['message'] ?? '', $ms, $result->finish ?? '?', $result->usage['input'] ?? '?', $result->usage['output'] ?? '?'));

        return self::SUCCESS;
    }
}
