<?php

namespace App\Services\Ai\Providers;

final class ChatResult
{
    public const FINISH_STOP = 'stop';

    public const FINISH_LENGTH = 'length';

    /**
     * @param 'stop'|'length'|string|null $finish normalised finish reason ('length' = truncated)
     * @param array{input: ?int, output: ?int} $usage
     */
    public function __construct(
        public readonly ?string $content,
        public readonly ?string $finish,
        public readonly array $usage = ['input' => null, 'output' => null],
    ) {}

    public function truncated(): bool
    {
        return $this->finish === self::FINISH_LENGTH;
    }
}
