<?php

namespace App\Console\Commands;

use App\Models\StockPhoto;
use Illuminate\Console\Command;

/**
 * Writes the stock photo catalog to database/seeders/data/stock_photos.json.gz so it ships
 * with the project: any environment gets the full catalog from `php artisan db:seed --class=StockPhotoSeeder`.
 */
class PhotosExport extends Command
{
    protected $signature = 'photos:export {--path=database/seeders/data/stock_photos.json.gz}';

    protected $description = 'Export the stock photo catalog to the seed file shipped with the project';

    public const COLUMNS = ['source', 'source_id', 'url', 'thumb_url', 'width', 'height', 'orientation', 'category', 'topic', 'alt', 'avg_color', 'photographer', 'photographer_url', 'ai_description', 'ai_tags', 'ai_subjects', 'people', 'person', 'mood', 'placements', 'analyzed_at'];

    public function handle(): int
    {
        $rows = StockPhoto::query()->orderBy('source')->orderBy('source_id')->get(self::COLUMNS)
            ->map(fn (StockPhoto $p) => array_map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d H:i:s') : $v, $p->only(self::COLUMNS)))
            ->all();
        $path = base_path($this->option('path'));
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, gzencode(json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 9));

        $this->info(count($rows) . ' photos written to ' . $this->option('path') . ' (' . round(filesize($path) / 1024) . ' KB)');

        return self::SUCCESS;
    }
}
