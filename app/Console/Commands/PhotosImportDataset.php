<?php

namespace App\Console\Commands;

use App\Models\StockPhoto;
use App\Services\Photos\PhotoCatalog;
use App\Services\Photos\PhotoTopics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Fills the catalog from the Unsplash Lite research dataset (https://unsplash.com/data):
 * ~25,000 real photos with descriptions and AI keywords, no API key needed. Photos are
 * matched to the site topics by keyword, the best per topic are kept, every image URL is
 * verified, and the keywords become searchable tags right away (photos:analyze can refine them).
 */
class PhotosImportDataset extends Command
{
    protected $signature = 'photos:import-dataset
        {dir : Folder holding photos.tsv000, keywords.tsv000 (and colors.tsv000) from the dataset zip}
        {--per-topic=10 : Photos to keep per topic (≈220 topics)}
        {--min-confidence=25 : Ignore dataset keywords below this confidence (0–100)}
        {--no-verify : Skip the HEAD request that checks each image really loads}';

    protected $description = 'Import 1,000+ stock photos from the Unsplash Lite dataset (no API key)';

    /** @var string[] words that say nothing about the subject */
    private const STOP = ['a', 'an', 'the', 'of', 'in', 'on', 'at', 'with', 'and', 'or', 'for', 'to', 'photo', 'image', 'picture', 'modern', 'cozy', 'luxury', 'elegant', 'natural', 'colorful', 'smiling', 'young', 'senior', 'diverse', 'healthy', 'fresh', 'old', 'new'];

    public function handle(): int
    {
        $dir = rtrim($this->argument('dir'), '/');
        foreach (['photos.tsv000', 'keywords.tsv000'] as $file) {
            if (!is_file("{$dir}/{$file}")) {
                $this->error("Missing {$dir}/{$file}");

                return self::FAILURE;
            }
        }
        $perTopic = max(1, (int) $this->option('per-topic'));
        $minConf = (float) $this->option('min-confidence');

        // 1. Which keywords matter: every topic token (and the topic phrase itself).
        $topics = PhotoTopics::all(withKeywordTopics: true);
        $wanted = [];     // keyword => true
        $topicTokens = []; // topic => tokens
        foreach ($topics as $t) {
            $tokens = $this->tokens($t['topic']);
            $topicTokens[$t['topic']] = $tokens;
            foreach ($tokens as $tok) {
                $wanted[$tok] = true;
            }
            $wanted[strtolower($t['topic'])] = true;
        }

        // 2. Stream the keyword file once: photo => matching keywords with confidence.
        $this->info('Reading keywords…');
        $byPhoto = [];     // photo_id => [keyword => confidence]
        $allKeywords = []; // photo_id => [keyword => confidence] (top ones, for tags)
        $fh = fopen("{$dir}/keywords.tsv000", 'r');
        $header = fgetcsv($fh, 0, "\t");
        $col = array_flip($header);
        $rows = 0;
        while (($row = fgetcsv($fh, 0, "\t")) !== false) {
            $rows++;
            $id = $row[$col['photo_id']] ?? null;
            $kw = strtolower(trim((string) ($row[$col['keyword']] ?? '')));
            if ($id === null || $kw === '') {
                continue;
            }
            // Service 1 reports 0–100, service 2 reports 0–1.
            $c2 = (float) ($row[$col['ai_service_2_confidence']] ?? 0);
            $conf = max((float) ($row[$col['ai_service_1_confidence']] ?? 0), $c2 <= 1 ? $c2 * 100 : $c2);
            if (($row[$col['suggested_by_user']] ?? 'f') === 't') {
                $conf = max($conf, 60); // the photographer's own tags are trustworthy
            }
            if ($conf < $minConf) {
                continue;
            }
            if (isset($wanted[$kw])) {
                $byPhoto[$id][$kw] = max($byPhoto[$id][$kw] ?? 0, $conf);
            }
            // Remember a few of the strongest keywords of every candidate photo for its tags.
            if (isset($byPhoto[$id]) || isset($allKeywords[$id])) {
                $allKeywords[$id][$kw] = max($allKeywords[$id][$kw] ?? 0, $conf);
                if (count($allKeywords[$id]) > 40) {
                    arsort($allKeywords[$id]);
                    $allKeywords[$id] = array_slice($allKeywords[$id], 0, 24, true);
                }
            }
        }
        fclose($fh);
        $this->line("  {$rows} keyword rows, " . count($byPhoto) . ' candidate photos');

        // 3. Photo metadata for the candidates only.
        $this->info('Reading photos…');
        $meta = [];
        $fh = fopen("{$dir}/photos.tsv000", 'r');
        $header = fgetcsv($fh, 0, "\t");
        $col = array_flip($header);
        while (($row = fgetcsv($fh, 0, "\t")) !== false) {
            $id = $row[$col['photo_id']] ?? null;
            if ($id === null || !isset($byPhoto[$id])) {
                continue;
            }
            $w = (int) ($row[$col['photo_width']] ?? 0);
            $h = (int) ($row[$col['photo_height']] ?? 0);
            $url = (string) ($row[$col['photo_image_url']] ?? '');
            if ($w < 1200 || $h < 800 || !str_starts_with($url, 'https://images.unsplash.com/')) {
                continue; // too small for a hero, or not hot-linkable
            }
            $meta[$id] = [
                'url' => $url,
                'source_id' => substr($url, strlen('https://images.unsplash.com/')), // "photo-1416…" — matches PhotoCatalog::keyFromUrl
                'width' => $w,
                'height' => $h,
                'featured' => ($row[$col['photo_featured']] ?? 'f') === 't',
                'description' => trim((string) ($row[$col['ai_description']] ?? '')) ?: trim((string) ($row[$col['photo_description']] ?? '')),
                'photographer' => trim(($row[$col['photographer_first_name']] ?? '') . ' ' . ($row[$col['photographer_last_name']] ?? '')),
                'photographer_url' => !empty($row[$col['photographer_username']]) ? 'https://unsplash.com/@' . $row[$col['photographer_username']] . '?utm_source=vibeweb&utm_medium=referral' : null,
            ];
        }
        fclose($fh);
        $this->line('  ' . count($meta) . ' candidates large enough');

        $colors = [];
        if (is_file("{$dir}/colors.tsv000")) {
            $fh = fopen("{$dir}/colors.tsv000", 'r');
            $header = fgetcsv($fh, 0, "\t");
            $col = array_flip($header);
            while (($row = fgetcsv($fh, 0, "\t")) !== false) {
                $id = $row[$col['photo_id']] ?? null;
                if ($id !== null && isset($meta[$id]) && !isset($colors[$id]) && !empty($row[$col['hex']])) {
                    $colors[$id] = '#' . ltrim($row[$col['hex']], '#');
                }
            }
            fclose($fh);
        }

        // 4. Pick the best photos per topic (a photo belongs to one topic only).
        $this->info('Choosing photos per topic…');
        $taken = [];
        $chosen = []; // id => [topic, category, score]
        $existing = StockPhoto::where('source', 'unsplash')->pluck('source_id')->flip()->all();
        foreach ($topics as $t) {
            $tokens = $topicTokens[$t['topic']];
            $phrase = strtolower($t['topic']);
            $primary = $tokens[0] ?? null;
            $scores = [];
            foreach ($byPhoto as $id => $kws) {
                if (isset($taken[$id]) || !isset($meta[$id])) {
                    continue;
                }
                $hits = 0;
                $score = 0.0;
                foreach ($tokens as $i => $tok) {
                    if (isset($kws[$tok])) {
                        $hits++;
                        $score += $kws[$tok] * ($i === 0 ? 1.5 : 1.0);
                    }
                }
                if (isset($kws[$phrase])) {
                    $hits = count($tokens);
                    $score += 150;
                }
                // The primary word must be there; one more word for multi-word topics.
                if (!$hits || ($primary && !isset($kws[$primary]) && !isset($kws[$phrase])) || (count($tokens) > 1 && $hits < 2 && !isset($kws[$phrase]))) {
                    continue;
                }
                $m = $meta[$id];
                $landscape = $m['width'] > $m['height'] * 1.15;
                if ($t['category'] === 'people' ? $landscape : !$landscape) {
                    $score *= 0.7; // wrong shape for the job
                }
                if ($m['featured']) {
                    $score *= 1.15;
                }
                $scores[$id] = $score;
            }
            arsort($scores);
            foreach (array_slice(array_keys($scores), 0, $perTopic) as $id) {
                $taken[$id] = true;
                if (isset($existing[$meta[$id]['source_id']])) {
                    continue;
                }
                $chosen[$id] = ['topic' => $t['topic'], 'category' => $t['category'], 'score' => $scores[$id]];
            }
        }
        $this->line('  ' . count($chosen) . ' photos selected across ' . count(array_unique(array_column($chosen, 'topic'))) . ' topics');

        // Keep only what the chosen photos need before the (long) verification loop.
        $allKeywords = array_intersect_key($allKeywords, $chosen);
        $meta = array_intersect_key($meta, $chosen);
        unset($byPhoto, $taken, $existing);
        gc_collect_cycles();

        // 5. Verify the images load (HEAD, 20 at a time) and store.
        $this->info($this->option('no-verify') ? 'Storing…' : 'Verifying images load and storing…');
        $bar = $this->output->createProgressBar(count($chosen));
        $bar->start();
        $added = $broken = 0;
        foreach (array_chunk(array_keys($chosen), 20) as $ids) {
            $ok = array_fill_keys($ids, true);
            if (!$this->option('no-verify')) {
                $responses = Http::pool(fn ($pool) => array_map(fn ($id) => $pool->as($id)->timeout(10)->head($meta[$id]['url'] . '?w=100&q=10&auto=format'), $ids));
                foreach ($ids as $id) {
                    $ok[$id] = isset($responses[$id]) && !($responses[$id] instanceof \Throwable) && $responses[$id]->successful();
                }
            }
            foreach ($ids as $id) {
                $bar->advance();
                if (!$ok[$id]) {
                    $broken++;
                    continue;
                }
                $m = $meta[$id];
                $kws = $allKeywords[$id] ?? [];
                arsort($kws);
                $tags = array_values(array_filter(array_map(fn ($k) => preg_replace('/[^\p{L}\p{N} \-]/u', '', $k), array_slice(array_keys($kws), 0, 14)), fn ($k) => $k !== '' && mb_strlen($k) <= 40));
                $ratio = $m['height'] > 0 ? $m['width'] / $m['height'] : 1;
                $people = (bool) array_intersect(['person', 'people', 'man', 'woman', 'portrait', 'face', 'human'], array_keys($kws));
                $photo = new StockPhoto([
                    'source' => 'unsplash',
                    'source_id' => $m['source_id'],
                    'url' => $m['url'],
                    'thumb_url' => $m['url'] . '?w=400&h=300&q=60&auto=format&fit=crop',
                    'width' => min(65535, $m['width']),
                    'height' => min(65535, $m['height']),
                    'orientation' => $ratio > 1.15 ? 'landscape' : ($ratio < 0.87 ? 'portrait' : 'square'),
                    'category' => $chosen[$id]['category'],
                    'topic' => $chosen[$id]['topic'],
                    'alt' => mb_strimwidth($m['description'], 0, 300, ''),
                    'avg_color' => $colors[$id] ?? null,
                    'photographer' => $m['photographer'] ?: null,
                    'photographer_url' => $m['photographer_url'],
                    // Dataset keywords make the photo searchable now; photos:analyze can refine them later.
                    'ai_description' => $m['description'] ?: null,
                    'ai_tags' => $tags,
                    'ai_subjects' => array_values(array_intersect($this->tokens($chosen[$id]['topic']), array_keys($kws))) ?: array_slice($tags, 0, 1),
                    'people' => $people,
                    'person' => $people ? (isset($kws['woman']) && !isset($kws['man']) ? 'woman' : (isset($kws['man']) && !isset($kws['woman']) ? 'man' : null)) : null,
                    'placements' => $ratio > 1.15 ? ['hero', 'card', 'gallery'] : ($people ? ['portrait', 'card'] : ['card', 'gallery']),
                ]);
                $photo->search_text = $photo->buildSearchText();
                $photo->save();
                $added++;
            }
        }
        $bar->finish();
        $this->newLine(2);

        PhotoCatalog::forget();
        $this->info("Added {$added} photos ({$broken} unreachable skipped). Catalog now holds " . StockPhoto::count() . ' photos.');

        return self::SUCCESS;
    }

    /** @return string[] subject words of a topic, most important first */
    private function tokens(string $topic): array
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower($topic), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($words as $w) {
            if (in_array($w, self::STOP, true)) {
                continue;
            }
            // Dataset keywords are mostly singular.
            if (strlen($w) > 3 && str_ends_with($w, 'ies')) {
                $w = substr($w, 0, -3) . 'y';
            } elseif (strlen($w) > 3 && str_ends_with($w, 's') && !str_ends_with($w, 'ss')) {
                $w = substr($w, 0, -1);
            }
            $out[] = $w;
        }

        return array_values(array_unique($out));
    }
}
