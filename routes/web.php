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

Route::get('/demo', fn () => Inertia::render('Demo'));

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

    // Autosave
    Route::post('/projects/{project}/pages/{page}/autosave', [PageController::class, 'autosave'])->name('pages.autosave');

    // Export
    Route::get('/projects/{project}/export', [ExportController::class, 'download'])->name('export.download');
    Route::get('/projects/{project}/preview-html', [ExportController::class, 'preview'])->name('export.preview');

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
    Route::get('/projects/{project}/analytics', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public site
Route::get('/s/{slug}', [PublicSiteController::class, 'show'])->name('site.show');

// Analytics beacon (no auth)
Route::post('/track', [AnalyticsController::class, 'store'])->name('analytics.track');

require __DIR__.'/auth.php';
