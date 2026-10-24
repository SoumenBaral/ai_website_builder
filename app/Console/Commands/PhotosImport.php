<?php

namespace App\Console\Commands;

use App\Models\StockPhoto;
use App\Services\Photos\PexelsClient;
use App\Services\Photos\PhotoCatalog;
use App\Services\Photos\PhotoTopics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Fills the stock photo catalog from Pexels, topic by topic. Safe to re-run:
 * photos already present are skipped, and a rate-limit answer pauses instead of failing.
 */
class PhotosImport extends Command
{
    protected $signature = 'photos:import
        {--per-topic=20 : Photos to fetch per topic}
        {--topic=* : Only these topics (default: all in PhotoTopics)}
        {--category=* : Only topics in these categories}
        {--no-verify : Skip the HEAD request that checks each image really loads}
        {--dry-run : Show what would be fetched without calling the API}';

    protected $description = 'Import 2,000+ stock photos from Pexels into the catalog (needs PEXELS_API_KEY)';

    public function handle(): int
    {
        $key = config('ai.photos.pexels_key');
        if (!$key && !$this->option('dry-run')) {
            $this->error('PEXELS_API_KEY is not set. Create a free key at https://www.pexels.com/api/ and add it to .env.');

            return self::FAILURE;
        }

        $topics = PhotoTopics::all();
        if ($only = $this->option('topic')) {
            $topics = array_values(array_filter($topics, fn ($t) => in_array($t['topic'], $only, true)));
        }
        if ($cats = $this->option('category')) {
            $topics = array_values(array_filter($topics, fn ($t) => in_array($t['category'], $cats, true)));
        }
        $perTopic = max(1, (int) $this->option('per-topic'));
        $this->info(sprintf('%d topics × %d photos ≈ %d photos (catalog has %d now)', count($topics), $perTopic, count($topics) * $perTopic, StockPhoto::count()));

        if ($this->option('dry-run')) {
            foreach ($topics as $t) {
                $this->line("  [{$t['category']}] {$t['topic']}");
            }

            return self::SUCCESS;
        }

        $client = new PexelsClient($key);
        $added = $skipped = $broken = 0;
        $bar = $this->output->createProgressBar(count($topics));
        $bar->start();

        foreach ($topics as $t) {
            // Every topic already full? Skip the API call entirely.
            if (StockPhoto::where('topic', $t['topic'])->count() >= $perTopic) {
                $bar->advance();
                continue;
            }

            $page = 1;
            $have = StockPhoto::where('topic', $t['topic'])->count();
            while ($have < $perTopic && $page <= 3) {
                try {
                    $result = $client->search($t['topic'], min(80, $perTopic * 2), $page, $t['category'] === 'people' ? 'portrait' : null);
                } catch (\RuntimeException $e) {
                    if (str_contains($e->getMessage(), 'HTTP 429')) {
                        $this->newLine();
                        $this->warn('Pexels rate limit reached — waiting 60s…');
                        sleep(60);
                        continue;
                    }
                    $this->newLine();
                    $this->error("{$t['topic']}: {$e->getMessage()}");
                    break;
                }

                if (!$result['photos']) {
                    break;
                }
                foreach ($result['photos'] as $photo) {
                    if ($have >= $perTopic) {
                        break;
                    }
                    if (!$photo['url'] || StockPhoto::where('source', 'pexels')->where('source_id', $photo['source_id'])->exists()) {
                        $skipped++;
                        continue;
                    }
                    if (!$this->option('no-verify') && !$this->loads($photo['thumb_url'])) {
                        $broken++;
                        continue;
                    }
                    $row = new StockPhoto($photo + ['category' => $t['category'], 'topic' => $t['topic']]);
                    $row->search_text = $row->buildSearchText();
                    $row->save();
                    $added++;
                    $have++;
                }
                $page++;
                if ($result['remaining'] !== null && $result['remaining'] < 3) {
                    $this->newLine();
                    $this->warn('Pexels hourly quota nearly used — waiting 60s…');
                    sleep(60);
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        PhotoCatalog::forget();
        $total = StockPhoto::count();
        $this->info("Added {$added} photos (skipped {$skipped} duplicates, {$broken} unreachable). Catalog: {$total} photos, " . StockPhoto::whereNull('analyzed_at')->count() . ' not yet analyzed.');
        if (StockPhoto::whereNull('analyzed_at')->exists()) {
            $this->line('Next: php artisan photos:analyze');
        }

        return self::SUCCESS;
    }

    private function loads(string $url): bool
    {
        try {
            return Http::timeout(8)->head($url)->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
