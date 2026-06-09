<?php

namespace App\Services;

class ImageResolver
{
    private const FALLBACK_IMAGES = [
        'hero' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&q=80',
        'team' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&q=80',
        'office' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&q=80',
        'nature' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&q=80',
        'food' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80',
        'tech' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&q=80',
        'portrait' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80',
        'city' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=600&q=80',
        'business' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600&q=80',
        'default' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=600&q=80',
    ];

    public function resolvePlaceholders(array $schema): array
    {
        $counter = 0;
        array_walk_recursive($schema, function (&$value) use (&$counter) {
            if (is_string($value) && str_starts_with($value, 'PLACEHOLDER:')) {
                $desc = strtolower(trim(substr($value, 12)));
                $value = $this->resolveImage($desc, $counter++);
            }
        });

        return $schema;
    }

    private function resolveImage(string $description, int $index): string
    {
        foreach (self::FALLBACK_IMAGES as $keyword => $url) {
            if (str_contains($description, $keyword)) {
                return $url . '&sig=' . md5($description . $index);
            }
        }

        return 'https://images.unsplash.com/photo-' . $this->getUnsplashId($description) . '?w=600&q=80&sig=' . md5($description . $index);
    }

    private function getUnsplashId(string $description): string
    {
        $ids = [
            '1504384308090-c894fdcc538d',
            '1497366216548-37526070297c',
            '1522071820081-009f0129c71c',
            '1506744038136-46273834b3fb',
            '1469474968028-56623f02e42e',
            '1470071459604-3b5ec3a7fe05',
        ];

        $hash = crc32($description);
        return $ids[abs($hash) % count($ids)];
    }
}
