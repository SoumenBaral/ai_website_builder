<?php

namespace App\Console\Commands;

use App\Models\StockPhoto;
use App\Services\Ai\Providers\ProviderException;
use App\Services\Ai\Providers\ProviderResolver;
use App\Services\Photos\PhotoAnalyzer;
use App\Services\Photos\PhotoCatalog;
use Illuminate\Console\Command;

/**
 * Runs the vision model over every photo that has not been described yet.
 * Resumable: stop it any time and run it again.
 */
class PhotosAnalyze extends Command
{
    protected $signature = 'photos:analyze
        {--limit=0 : Stop after this many photos (0 = all)}
        {--provider= : openai|puku (default: AI_PHOTO_ANALYZE_PROVIDER, then the main provider)}
        {--model= : Vision model to use (default: the provider\'s edit model)}
        {--redo : Re-analyze photos that already have a description}';

    protected $description = 'Describe catalog photos with a vision model so the AI can pick them accurately';

    public function handle(): int
    {
        $providerName = $this->option('provider') ?: config('ai.photos.analyze_provider');
        try {
            $provider = app(ProviderResolver::class)->resolve($providerName ?: null);
        } catch (\Throwable $e) {
            $this->error('Could not set up the AI provider: ' . $e->getMessage());

            return self::FAILURE;
        }
        $model = $this->option('model') ?: config('ai.photos.analyze_model') ?: $provider->model('edit');
        $analyzer = new PhotoAnalyzer($provider);

        $redo = (bool) $this->option('redo');
        $pending = fn () => $redo ? StockPhoto::query()->orderBy('id') : StockPhoto::query()->whereNull('analyzed_at')->orderBy('id');
        $total = $pending()->count();
        $limit = (int) $this->option('limit') ?: $total;
        $todo = min($total, $limit);
        $this->info("Analyzing {$todo} of {$total} pending photos with {$provider->label()} / {$model} (" . PhotoAnalyzer::BATCH . ' per request)');
        if ($todo === 0) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($todo);
        $bar->start();
        $done = $failed = 0;
        $consecutiveFailures = 0;

        while ($done + $failed < $todo) {
            // --redo walks the whole table by offset; otherwise the pending set shrinks by itself.
            $batch = $pending()->skip($redo ? $done + $failed : 0)->take(min(PhotoAnalyzer::BATCH, $todo - $done - $failed))->get();
            if ($batch->isEmpty()) {
                break;
            }

            try {
                $saved = $analyzer->analyze($batch, $model);
                $consecutiveFailures = 0;
                $done += $saved;
                $missed = $batch->count() - $saved;
                if ($missed > 0) {
                    $failed += $missed;
                    // Mark as attempted so a flaky photo can't stall the loop; `--redo` picks it up again.
                    foreach ($batch->whereNull('analyzed_at') as $p) {
                        $p->forceFill(['analyzed_at' => now()])->save();
                    }
                }
                $bar->advance($batch->count());
            } catch (ProviderException $e) {
                if (in_array($e->kind, [ProviderException::QUOTA, ProviderException::REJECTED], true)) {
                    $this->newLine();
                    $this->error("{$provider->label()} ({$e->kind}): {$e->getMessage()}");
                    break;
                }
                if (++$consecutiveFailures >= 5) {
                    $this->newLine();
                    $this->error('Giving up after 5 consecutive provider errors: ' . $e->getMessage());
                    break;
                }
                $this->newLine();
                $this->warn("Provider error, retrying in 10s: {$e->getMessage()}");
                sleep(10);
            } catch (\RuntimeException $e) {
                if (++$consecutiveFailures >= 5) {
                    $this->newLine();
                    $this->error('Giving up after 5 consecutive unusable answers: ' . $e->getMessage());
                    break;
                }
                $this->newLine();
                $this->warn('Unusable answer, retrying: ' . $e->getMessage());
            }
        }
        $bar->finish();
        $this->newLine(2);

        PhotoCatalog::forget();
        $this->info("Described {$done} photos" . ($failed ? " ({$failed} could not be described; run with --redo to retry)" : '') . '. Remaining: ' . StockPhoto::whereNull('analyzed_at')->count());

        return self::SUCCESS;
    }
}
