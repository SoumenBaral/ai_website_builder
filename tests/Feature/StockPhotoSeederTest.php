<?php

use App\Models\StockPhoto;
use Database\Seeders\StockPhotoSeeder;

it('ships the stock photo catalog with the project and seeds it idempotently', function () {
    $file = base_path(StockPhotoSeeder::FILE);
    expect(is_file($file))->toBeTrue('Run: php artisan photos:export');

    $rows = json_decode(gzdecode(file_get_contents($file)), true);
    expect(count($rows))->toBeGreaterThan(1000)
        ->and(collect($rows)->every(fn ($r) => str_starts_with($r['url'], 'https://images.unsplash.com/') || str_starts_with($r['url'], 'https://images.pexels.com/')))->toBeTrue()
        ->and(collect($rows)->pluck('category')->unique()->count())->toBeGreaterThanOrEqual(15);

    $this->seed(StockPhotoSeeder::class);
    expect(StockPhoto::count())->toBe(count($rows))
        ->and(StockPhoto::whereNull('search_text')->count())->toBe(0)
        ->and(app(\App\Services\Photos\PhotoCatalog::class)->count())->toBe(count($rows));

    $this->seed(StockPhotoSeeder::class); // re-running never duplicates
    expect(StockPhoto::count())->toBe(count($rows));
});
