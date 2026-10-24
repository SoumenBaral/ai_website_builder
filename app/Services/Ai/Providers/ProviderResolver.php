<?php

namespace App\Services\Ai\Providers;

/**
 * Picks the AI provider from config/ai.php.
 *
 *   AI_PROVIDER=openai|puku  → that provider, with its own key.
 *   AI_PROVIDER=auto         → decided by the API key: a `pk_live_…` key is Puku,
 *                              an `sk-…` key is OpenAI. AI_API_KEY is checked first,
 *                              then OPENAI_API_KEY / PUKU_API_KEY — so pasting a Puku
 *                              key into OPENAI_API_KEY still works.
 */
class ProviderResolver
{
    public const PROVIDERS = ['openai', 'puku'];

    public function __construct(private array $config) {}

    public static function fromConfig(): self
    {
        return new self(config('ai'));
    }

    /** @return array{provider: string, key: ?string, source: string} */
    public function detect(?string $force = null): array
    {
        $forced = $force ?: strtolower((string) ($this->config['provider'] ?? 'auto'));
        $keys = [
            'AI_API_KEY' => $this->config['api_key'] ?? null,
            'OPENAI_API_KEY' => $this->config['providers']['openai']['api_key'] ?? null,
            'PUKU_API_KEY' => $this->config['providers']['puku']['api_key'] ?? null,
        ];

        if (in_array($forced, self::PROVIDERS, true)) {
            // Explicit choice: use that provider's key, or any key that looks like it belongs to it.
            foreach ($keys as $source => $key) {
                if ($key && self::kindOf($key) === $forced) {
                    return ['provider' => $forced, 'key' => $key, 'source' => $source];
                }
            }
            $own = $keys[$forced === 'puku' ? 'PUKU_API_KEY' : 'OPENAI_API_KEY'] ?: $keys['AI_API_KEY'];

            return ['provider' => $forced, 'key' => $own ?: null, 'source' => $own ? ($forced === 'puku' ? 'PUKU_API_KEY' : 'OPENAI_API_KEY') : 'none'];
        }

        // auto: the first key present decides, by its prefix.
        foreach ($keys as $source => $key) {
            if ($key) {
                return ['provider' => self::kindOf($key) ?? 'openai', 'key' => $key, 'source' => $source];
            }
        }

        return ['provider' => 'openai', 'key' => null, 'source' => 'none'];
    }

    public function resolve(?string $force = null): ChatProvider
    {
        $detected = $this->detect($force);
        $providers = $this->config['providers'] ?? [];

        if ($detected['provider'] === 'puku') {
            return new PukuProvider(
                apiKey: (string) $detected['key'],
                models: $providers['puku']['models'] ?? ['generate' => 'opus', 'edit' => 'puku-ai-2.8'],
                baseUrl: $providers['puku']['base_url'] ?? PukuProvider::DEFAULT_BASE_URL,
                timeout: (int) ($this->config['request_timeout'] ?? 150),
            );
        }

        // The openai-php client reads its key from config('openai.api_key'); honour a key
        // that was pasted into AI_API_KEY instead.
        if ($detected['key'] && config('openai.api_key') !== $detected['key']) {
            config(['openai.api_key' => $detected['key']]);
        }

        return new OpenAiProvider($providers['openai']['models'] ?? ['generate' => 'gpt-4.1', 'edit' => 'gpt-4.1-mini']);
    }

    /** "puku" for pk_live_/pk_test_ keys, "openai" for sk- keys, null when unknown. */
    public static function kindOf(string $key): ?string
    {
        $key = trim($key);

        return match (true) {
            str_starts_with($key, 'pk_live_'), str_starts_with($key, 'pk_test_'), str_starts_with($key, 'pk_') => 'puku',
            str_starts_with($key, 'sk-') => 'openai',
            default => null,
        };
    }

    /** "pk_live_…UYu0" — safe to print. */
    public static function mask(?string $key): string
    {
        if (!$key) {
            return '(not set)';
        }
        if (strlen($key) <= 12) {
            return substr($key, 0, 3) . '…';
        }

        return substr($key, 0, 8) . '…' . substr($key, -4);
    }
}
