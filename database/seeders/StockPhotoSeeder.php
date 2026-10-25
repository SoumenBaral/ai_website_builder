<?php

namespace Database\Seeders;

use App\Models\StockPhoto;
use App\Services\Photos\PhotoCatalog;
use Illuminate\Database\Seeder;

/**
 * Loads the stock photo catalog shipped with the project (database/seeders/data/stock_photos.json.gz,
 * written by `photos:export`). Safe to re-run: rows are matched on source + source_id.
 */
class StockPhotoSeeder extends Seeder
{
    public const FILE = 'database/seeders/data/stock_photos.json.gz';

    public function run(): void
    {
        $path = base_path(self::FILE);
        if (!is_file($path)) {
            $this->command?->warn('No catalog file at ' . self::FILE . ' — run photos:import-dataset then photos:export.');

            return;
        }

        $rows = json_decode(gzdecode(file_get_contents($path)), true) ?: [];
        $now = now()->format('Y-m-d H:i:s');
        foreach (array_chunk($rows, 500) as $chunk) {
            $records = array_map(function (array $row) use ($now) {
                $row['search_text'] = (new StockPhoto($row))->buildSearchText();
                foreach (['ai_tags', 'ai_subjects', 'placements'] as $json) {
                    $row[$json] = isset($row[$json]) ? json_encode($row[$json]) : null;
                }
                $row['created_at'] = $now;
                $row['updated_at'] = $now;

                return $row;
            }, $chunk);
            StockPhoto::upsert($records, ['source', 'source_id'], array_diff(array_keys($records[0]), ['source', 'source_id', 'created_at']));
        }

        PhotoCatalog::forget();
        $this->command?->info(count($rows) . ' stock photos seeded (catalog: ' . StockPhoto::count() . ').');
    }
}
