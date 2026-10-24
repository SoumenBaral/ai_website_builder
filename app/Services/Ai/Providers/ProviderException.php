<?php

namespace App\Services\Ai\Providers;

use RuntimeException;

/**
 * A failure talking to an AI provider, classified so the caller can decide
 * whether to retry without knowing which vendor is behind it.
 */
class ProviderException extends RuntimeException
{
    /** Too many requests right now; back off and try again later. */
    public const RATE_LIMITED = 'rate_limited';

    /** The account's plan/credits are exhausted; retrying won't help until it's topped up. */
    public const QUOTA = 'quota';

    /** The provider rejected the request (bad key, invalid schema, unknown model). */
    public const REJECTED = 'rejected';

    /** Network / 5xx / unreadable response: safe to retry. */
    public const TRANSPORT = 'transport';

    public function __construct(
        public readonly string $kind,
        string $message,
        public readonly ?int $status = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function retryable(): bool
    {
        return $this->kind === self::TRANSPORT;
    }
}
