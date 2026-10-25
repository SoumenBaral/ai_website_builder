<?php

namespace App\Services\Ai\Providers;

/**
 * A chat-completion backend that can return JSON constrained by a schema.
 *
 * Implementations throw ProviderException for every vendor failure so that
 * AiWebsiteService never has to know which vendor is configured.
 */
interface ChatProvider
{
    /** Short identifier used in config, logs and ai:doctor ("openai", "puku"). */
    public function name(): string;

    /** Human-readable label ("OpenAI", "Puku"). */
    public function label(): string;

    /** The configured model for a purpose: 'generate' (full sites) or 'edit' (small changes). */
    public function model(string $purpose): string;

    /**
     * @throws ProviderException
     */
    public function complete(ChatRequest $request): ChatResult;

    /**
     * Stream the response; $onDelta receives each piece of text as it arrives.
     *
     * @param callable(string): void $onDelta
     * @throws ProviderException
     */
    public function stream(ChatRequest $request, callable $onDelta): ChatResult;
}
