<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * An AI failure with a message that is safe to show to the user.
 * Technical details go to the log, never to the browser.
 */
class AiException extends RuntimeException
{
    public function __construct(string $userMessage, public readonly int $status = 502, ?\Throwable $previous = null)
    {
        parent::__construct($userMessage, 0, $previous);
    }

    public static function limitReached(int $limit): self
    {
        return new self("You've used all {$limit} AI generations for this month. Upgrade your plan for more, or keep editing by hand.", 429);
    }

    public static function unusable(?\Throwable $previous = null): self
    {
        return new self('The AI returned an incomplete result. Please try again — rephrasing the request can help.', 502, $previous);
    }

    /** The provider's own plan/credits are exhausted — an operator problem, not a user one. */
    public static function providerQuota(string $provider, ?\Throwable $previous = null): self
    {
        return new self("The {$provider} account behind this site has used up its plan quota. Please check the {$provider} subscription, or switch AI provider, then try again.", 503, $previous);
    }

    public static function unavailable(?\Throwable $previous = null): self
    {
        return new self('The AI service is busy or unreachable right now. Please try again in a moment.', 503, $previous);
    }
}
