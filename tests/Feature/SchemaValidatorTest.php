<?php

use App\Services\SchemaValidator;

beforeEach(function () {
    $this->validator = new SchemaValidator();

    $this->validSchema = [
        'version' => 1,
        'meta' => ['title' => 'Test', 'description' => 'Test site', 'language' => 'en'],
        'theme' => [
            'primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a',
            'backgroundColor' => '#ffffff', 'textColor' => '#0f172a',
            'fontHeading' => 'Poppins', 'fontBody' => 'Inter',
            'radius' => 'lg', 'mode' => 'light',
        ],
        'sections' => [
            ['id' => 'blk_hero_01', 'type' => 'hero', 'props' => ['heading' => 'Test']],
        ],
    ];
});

it('passes validation with a valid schema', function () {
    $result = $this->validator->validate($this->validSchema);

    expect($result)->toBe($this->validSchema);
});

it('throws exception when top-level keys are missing', function () {
    $schema = ['version' => 1];

    $this->validator->validate($schema);
})->throws(InvalidArgumentException::class, 'Schema missing required top-level keys');

it('throws exception for unknown block type', function () {
    $schema = $this->validSchema;
    $schema['sections'] = [
        ['id' => 'blk_test_01', 'type' => 'unknown_block', 'props' => ['heading' => 'Test']],
    ];

    $this->validator->validate($schema);
})->throws(InvalidArgumentException::class, 'unknown type');

it('throws exception when section keys are missing', function () {
    $schema = $this->validSchema;
    $schema['sections'] = [
        ['id' => 'blk_hero_01'],
    ];

    $this->validator->validate($schema);
})->throws(InvalidArgumentException::class, 'missing required keys');
