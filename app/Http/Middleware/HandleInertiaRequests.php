<?php

namespace App\Http\Middleware;

use App\Services\AiWebsiteService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Monthly AI generation usage for the app sidebar (evaluated lazily, signed-in users only).
            'usage' => fn () => $request->user()
                ? app(AiWebsiteService::class)->getRemainingGenerations($request->user()->id)
                : null,
        ];
    }
}
