<?php

/*
|--------------------------------------------------------------------------
| AI website generation
|--------------------------------------------------------------------------
| Models, sampling and limits for AiWebsiteService. Every value can be
| overridden from .env so you can tune quality / cost without code changes.
*/

return [

    'models' => [
        // Full-site generation: quality matters most.
        'generate' => env('AI_GENERATE_MODEL', 'gpt-4.1'),
        // Edits (full site or one section): fast and cheap, structure is enforced by the schema.
        'edit' => env('AI_EDIT_MODEL', 'gpt-4.1-mini'),
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
        'edit_section' => (int) env('AI_EDIT_SECTION_MAX_TOKENS', 4000),
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
    ],

    // Optional: real photo search. Without a key, a curated, verified library is used.
    'unsplash_access_key' => env('UNSPLASH_ACCESS_KEY'),
];
