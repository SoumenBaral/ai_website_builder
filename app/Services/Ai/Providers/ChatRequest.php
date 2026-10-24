<?php

namespace App\Services\Ai\Providers;

/**
 * A provider-neutral "give me JSON matching this schema" request.
 *
 * Messages use the OpenAI shape ({role, content}) with roles system|user|assistant;
 * each provider maps them to its own wire format.
 */
final class ChatRequest
{
    /**
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function __construct(
        public readonly string $model,
        public readonly array $messages,
        public readonly string $schemaName,
        public readonly array $schema,
        public readonly int $maxTokens,
        public readonly float $temperature,
    ) {}

    /** The system prompt (all system messages joined), or null when there is none. */
    public function systemPrompt(): ?string
    {
        $parts = [];
        foreach ($this->messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $parts[] = $message['content'];
            }
        }

        return $parts ? implode("\n\n", $parts) : null;
    }

    /** @return array<int, array{role: string, content: string}> user/assistant turns only */
    public function conversation(): array
    {
        return array_values(array_filter($this->messages, fn ($m) => ($m['role'] ?? '') !== 'system'));
    }
}
