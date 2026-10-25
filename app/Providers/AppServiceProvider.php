<?php

namespace App\Providers;

use App\Services\Ai\Providers\ChatProvider;
use App\Services\Ai\Providers\ProviderResolver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The AI backend (OpenAI or Puku) is chosen from config/ai.php at first use.
        $this->app->singleton(ProviderResolver::class, fn () => ProviderResolver::fromConfig());
        $this->app->singleton(ChatProvider::class, fn () => $this->app->make(ProviderResolver::class)->resolve());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
