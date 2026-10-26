<?php

/*
|--------------------------------------------------------------------------
| AI website generation
|--------------------------------------------------------------------------
| Models, sampling and limits for AiWebsiteService. Every value can be
| overridden from .env so you can tune quality / cost without code changes.
*/

return [

    /*
    | Which backend answers AI requests.
    |   auto   — decided by the API key: a `pk_live_…` key means Puku (puku.sh),
    |            an `sk-…` key means OpenAI. AI_API_KEY wins, then OPENAI_API_KEY / PUKU_API_KEY.
    |   openai — always OpenAI.
    |   puku   — always Puku.
    */
    'provider' => env('AI_PROVIDER', 'auto'),

    // One key to rule them all: paste either vendor's key here and `auto` works it out.
    'api_key' => env('AI_API_KEY'),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'models' => [
                // Full-site generation: quality matters most.
                'generate' => env('AI_GENERATE_MODEL', 'gpt-4.1'),
                // Edits (full site or one section): fast and cheap, structure is enforced by the schema.
                'edit' => env('AI_EDIT_MODEL', 'gpt-4.1-mini'),
            ],
        ],
        'puku' => [
            'api_key' => env('PUKU_API_KEY'),
            // Anthropic-Messages-compatible gateway used by the Puku CLI / agent SDK.
            'base_url' => env('PUKU_BASE_URL', 'https://api-cli.puku.sh'),
            'models' => [
                // `opus` is Puku's alias for the latest Claude Opus; puku-ai-2.8 / 2.7 are its own tiers.
                'generate' => env('PUKU_GENERATE_MODEL', 'opus'),
                'edit' => env('PUKU_EDIT_MODEL', 'puku-ai-2.8'),
            ],
        ],
    ],

    'temperature' => [
        // A little creative freedom for copy, palettes and layout choices.
        'generate' => (float) env('AI_GENERATE_TEMPERATURE', 0.8),
        // Edits should change only what was asked.
        'edit' => (float) env('AI_EDIT_TEMPERATURE', 0.3),
    ],

    'max_tokens' => [
        'generate' => (int) env('AI_GENERATE_MAX_TOKENS', 14000),
        'edit_site' => (int) env('AI_EDIT_SITE_MAX_TOKENS', 14000),
        'edit_section' => (int) env('AI_EDIT_SECTION_MAX_TOKENS', 8000),
    ],

    // Automatic retries when the model output is truncated or unusable.
    'retries' => (int) env('AI_RETRIES', 1),

    'request_timeout' => (int) env('AI_REQUEST_TIMEOUT', 120),

    // USD per 1M tokens [input, output], used for the cost column in ai_generations.
    'pricing' => [
        'gpt-4.1' => [2.00, 8.00],
        'gpt-4.1-mini' => [0.40, 1.60],
        'gpt-4.1-nano' => [0.10, 0.40],
        'gpt-4o' => [2.50, 10.00],
        'gpt-4o-mini' => [0.15, 0.60],
        // Puku bills per request against a subscription bucket, not per token; these are
        // list-price estimates for the underlying models so the cost column stays meaningful.
        'opus' => [5.00, 25.00],
        'puku-ai-2.8' => [3.00, 15.00],
        'puku-ai-2.7' => [3.00, 15.00],
    ],

    // Optional: real photo search. Without a key, a curated, verified library is used.
    'unsplash_access_key' => env('UNSPLASH_ACCESS_KEY'),

    /*
    | Stock photo catalog (stock_photos table): `photos:import` fills it from Pexels,
    | `photos:analyze` describes every photo with a vision model. When the catalog
    | holds photos, the AI resolver and the picker use it instead of the built-in library.
    */
    'photos' => [
        'pexels_key' => env('PEXELS_API_KEY'),
        // Which provider describes the photos (needs vision). Empty = the main AI provider.
        'analyze_provider' => env('AI_PHOTO_ANALYZE_PROVIDER', 'openai'),
        'analyze_model' => env('AI_PHOTO_ANALYZE_MODEL', 'gpt-4.1-mini'),
        // Let the AI choose between the top candidates for the hero / about photo (one small text call).
        'rerank' => (bool) env('AI_PHOTO_RERANK', true),
    ],
];
