<?php

namespace App\Services;

use InvalidArgumentException;

class SchemaValidator
{
    private const ALLOWED_TYPES = [
        'navbar', 'hero', 'features', 'gallery', 'about',
        'services', 'testimonials', 'pricing', 'stats',
        'faq', 'team', 'cta', 'contact', 'footer',
    ];

    public function validate(array $schema): array
    {
        if (!isset($schema['version'], $schema['meta'], $schema['theme'], $schema['sections'])) {
            throw new InvalidArgumentException('Schema missing required top-level keys: version, meta, theme, sections');
        }

        foreach (['title', 'description', 'language'] as $key) {
            if (!isset($schema['meta'][$key])) {
                throw new InvalidArgumentException("Schema meta missing key: {$key}");
            }
        }

        foreach (['primaryColor', 'backgroundColor', 'textColor', 'fontHeading', 'fontBody', 'radius', 'mode'] as $key) {
            if (!isset($schema['theme'][$key])) {
                throw new InvalidArgumentException("Schema theme missing key: {$key}");
            }
        }

        if (!is_array($schema['sections'])) {
            throw new InvalidArgumentException('Schema sections must be an array');
        }

        foreach ($schema['sections'] as $i => $section) {
            if (!isset($section['id'], $section['type'], $section['props'])) {
                throw new InvalidArgumentException("Section {$i} missing required keys: id, type, props");
            }
            if (!in_array($section['type'], self::ALLOWED_TYPES, true)) {
                throw new InvalidArgumentException("Section {$i} has unknown type: {$section['type']}");
            }
        }

        return $schema;
    }
}
