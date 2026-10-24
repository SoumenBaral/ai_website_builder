<?php

namespace App\Services\Ai\Providers;

/**
 * Makes an OpenAI-strict JSON schema acceptable to Anthropic structured outputs.
 *
 * Both require `additionalProperties: false` and every property listed in
 * `required`, so the BlockCatalog schemas already qualify. Anthropic additionally
 * rejects numeric/string/array bound keywords, so those are dropped (the
 * SchemaNormalizer re-checks ranges after the fact anyway).
 */
final class AnthropicSchema
{
    private const UNSUPPORTED = [
        'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf',
        'minLength', 'maxLength', 'maxItems', 'uniqueItems', 'minProperties', 'maxProperties',
    ];

    public static function sanitize(array $schema): array
    {
        foreach (self::UNSUPPORTED as $key) {
            unset($schema[$key]);
        }
        if (isset($schema['minItems']) && $schema['minItems'] > 1) {
            $schema['minItems'] = 1;
        }

        foreach ($schema as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            // `properties` / `$defs` are maps of schemas; everything else that is an array
            // is either a schema (items, anyOf entries) or a plain list (enum, required).
            if (in_array($key, ['properties', '$defs', 'definitions'], true)) {
                foreach ($value as $name => $sub) {
                    $schema[$key][$name] = is_array($sub) ? self::sanitize($sub) : $sub;
                }
            } elseif (in_array($key, ['enum', 'required', 'type'], true)) {
                continue;
            } elseif (array_is_list($value)) {
                $schema[$key] = array_map(fn ($v) => is_array($v) ? self::sanitize($v) : $v, $value);
            } else {
                $schema[$key] = self::sanitize($value);
            }
        }

        return $schema;
    }
}
