<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\VersionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/demo', [\App\Http\Controllers\DemoController::class, 'show'])->name('demo');
Route::get('/demo/{section}/{item}', [\App\Http\Controllers\DemoController::class, 'item'])
    ->where(['section' => '[a-z0-9-]+', 'item' => '[a-z0-9-]+'])->name('demo.item');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');

    // Projects
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Editor
    Route::get('/projects/{project}/editor', [EditorController::class, 'show'])->name('editor.show');

    // AI
    Route::post('/ai/generate', [AiController::class, 'generate'])->middleware('throttle:10,1')->name('ai.generate');
    Route::post('/ai/edit-block', [AiController::class, 'editBlock'])->middleware('throttle:20,1')->name('ai.edit');
    Route::post('/ai/edit-section', [AiController::class, 'editSection'])->middleware('throttle:20,1')->name('ai.editSection');
    Route::post('/ai/logo', [AiController::class, 'logo'])->middleware('throttle:20,1')->name('ai.logo');
    Route::post('/ai/animate', [AiController::class, 'animate'])->middleware('throttle:30,1')->name('ai.animate');
    Route::post('/ai/photos', [AiController::class, 'photos'])->middleware('throttle:30,1')->name('ai.photos');
    Route::post('/projects/{project}/logo', [AiController::class, 'saveLogo'])->name('projects.logo');
    Route::post('/projects/{project}/favicon', [\App\Http\Controllers\FaviconController::class, 'store'])->middleware('throttle:30,1')->name('projects.favicon');
    Route::delete('/projects/{project}/favicon', [\App\Http\Controllers\FaviconController::class, 'destroy'])->name('projects.favicon.destroy');

    // Autosave
    Route::post('/projects/{project}/pages/{page}/autosave', [PageController::class, 'autosave'])->name('pages.autosave');

    // Export
    Route::get('/projects/{project}/export', [ExportController::class, 'download'])->name('export.download');
    Route::get('/projects/{project}/preview-html', [ExportController::class, 'preview'])->name('export.preview');
    Route::get('/projects/{project}/preview-html/{section}/{item}', [ExportController::class, 'previewItem'])
        ->where(['section' => '[a-z0-9-]+', 'item' => '[a-z0-9-]+'])->name('export.previewItem');
    Route::get('/projects/{project}/preview-card/{sectionId}/{index}', [ExportController::class, 'previewCard'])
        ->whereNumber('index')->name('export.previewCard');

    // Publish
    Route::post('/projects/{project}/publish', [PublishController::class, 'publish'])->name('projects.publish');
    Route::post('/projects/{project}/unpublish', [PublishController::class, 'unpublish'])->name('projects.unpublish');

    // Versions
    Route::get('/projects/{project}/versions', [VersionController::class, 'index'])->name('versions.index');
    Route::post('/versions/{version}/restore', [VersionController::class, 'restore'])->name('versions.restore');

    // Assets (image upload)
    Route::get('/projects/{project}/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::post('/projects/{project}/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::delete('/projects/{project}/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

    // Analytics
    Route::get('/stock-photos', [\App\Http\Controllers\StockPhotoController::class, 'index'])->middleware('throttle:60,1')->name('stock.index');
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'all'])->name('orders.all');
    Route::get('/projects/{project}/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::patch('/projects/{project}/orders/{order}', [\App\Http\Controllers\OrderController::class, 'update'])->name('orders.update');
    Route::get('/projects/{project}/analytics', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public site + analytics beacon: no session, cookies, CSRF or Inertia — anonymous visitors
// get cacheable responses and never create session rows.
Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
    \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
])->group(function () {
    Route::get('/s/{slug}', [PublicSiteController::class, 'show'])->name('site.show');
    Route::get('/s/{slug}/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('site.sitemap');
    Route::get('/s/{slug}/robots.txt', [PublicSiteController::class, 'robots'])->name('site.robots');
    Route::get('/s/{slug}/{section}/{item}', [PublicSiteController::class, 'item'])
        ->where(['section' => '[a-z0-9-]+', 'item' => '[a-z0-9-]+'])->name('site.item');
    Route::post('/track', [AnalyticsController::class, 'store'])->middleware('throttle:60,1')->name('analytics.track');
});
Route::post('/s/{slug}/orders', [\App\Http\Controllers\OrderController::class, 'store'])->middleware('throttle:8,1')->name('site.order');

require __DIR__.'/auth.php';
