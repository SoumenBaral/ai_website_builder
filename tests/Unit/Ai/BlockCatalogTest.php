<?php

use App\Services\Ai\BlockCatalog;

/** Walk a JSON schema and collect strict-mode violations. */
function strictViolations(array $node, string $path = '$'): array
{
    $problems = [];
    $types = (array) ($node['type'] ?? []);
    if (in_array('object', $types, true)) {
        if (($node['additionalProperties'] ?? null) !== false) {
            $problems[] = "{$path}: additionalProperties must be false";
        }
        $keys = array_keys($node['properties'] ?? []);
        $required = $node['required'] ?? [];
        sort($keys);
        sort($required);
        if ($keys !== $required) {
            $problems[] = "{$path}: every property must be required";
        }
        foreach ($node['properties'] ?? [] as $key => $child) {
            $problems = [...$problems, ...strictViolations($child, "{$path}.{$key}")];
        }
    }
    if (isset($node['items'])) {
        $problems = [...$problems, ...strictViolations($node['items'], "{$path}[]")];
    }
    foreach ($node['anyOf'] ?? [] as $i => $option) {
        $problems = [...$problems, ...strictViolations($option, "{$path}|{$i}")];
    }

    return $problems;
}

it('produces strict-mode compatible schemas', function () {
    expect(strictViolations(BlockCatalog::siteSchema('plan')))->toBe([]);
    expect(strictViolations(BlockCatalog::siteSchema('summary')))->toBe([]);
    foreach (BlockCatalog::types() as $type) {
        expect(strictViolations(BlockCatalog::sectionEditSchema($type)))->toBe([], "section edit schema for {$type}");
    }
});

it('limits theme fonts to the editor font list', function () {
    $theme = BlockCatalog::siteSchema()['properties']['theme']['properties'];

    expect($theme['fontHeading']['enum'])->toBe(BlockCatalog::FONTS)
        ->and($theme['fontBody']['enum'])->toBe(BlockCatalog::FONTS);
});

it('covers every block the editor can render', function () {
    $vueBlocks = collect(glob(dirname(__DIR__, 3) . ('/resources/js/Components/builder/blocks/Block*.vue')))
        ->map(fn ($f) => strtolower(preg_replace('/^Block|\.vue$/', '', basename($f))))
        ->sort()->values()->all();

    $catalog = BlockCatalog::types();
    sort($catalog);

    expect($catalog)->toBe($vueBlocks);
});

it('only describes props the Vue blocks actually read (guards against prompt drift)', function () {
    foreach (BlockCatalog::types() as $type) {
        $source = file_get_contents(dirname(__DIR__, 3) . ('/resources/js/Components/builder/blocks/Block' . ucfirst($type) . '.vue'));

        foreach (BlockCatalog::props($type) as $key => $spec) {
            if (in_array($key, BlockCatalog::WRAPPER_PROPS, true)) {
                continue; // applied by BlockRenderer, not the block itself
            }
            expect(str_contains($source, $key))->toBeTrue("{$type}.{$key} is not used by Block" . ucfirst($type) . '.vue');

            foreach (array_keys($spec['fields'] ?? []) as $field) {
                expect($source)->toMatch('/\.' . preg_quote($field, '/') . '\b/', "{$type}.{$key}[].{$field} is not read by the block");
            }
        }
    }
});

it('gives sections stable, readable anchors', function () {
    $anchors = BlockCatalog::anchors([
        ['type' => 'navbar'], ['type' => 'hero'], ['type' => 'about'],
        ['type' => 'gallery'], ['type' => 'gallery'], ['type' => 'footer'],
    ]);

    expect($anchors)->toBe([null, 'home', 'about', 'gallery', 'gallery-2', null]);
});
