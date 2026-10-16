<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use App\Services\Ai\AiException;
use App\Services\AiWebsiteService;
use App\Services\Logo\LogoDesigner;
use App\Services\Logo\LogoRenderer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiController extends Controller
{
    /**
     * Generate a full website. Streams progress as NDJSON when the client sends
     * `Accept: application/x-ndjson` (keeps long generations alive behind
     * proxies and powers the live progress UI); otherwise returns plain JSON.
     */
    public function generate(Request $request, AiWebsiteService $ai)
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:2000',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $project = $request->project_id ? Project::find($request->project_id) : null;
        if ($project && $project->user_id !== $request->user()->id) {
            abort(403);
        }

        $run = function (?callable $progress) use ($request, $ai, $project) {
            $schema = $ai->generate($request->prompt, $request->user()->id, $project?->id, $progress);
            if ($project) {
                $this->saveWithVersion($project, $schema, 'Before AI generation', $request->user()->id);
            }

            return ['schema' => $schema];
        };

        return $this->respond($request, $run);
    }

    /** Apply an instruction to the whole site. */
    public function editBlock(Request $request, AiWebsiteService $ai)
    {
        $request->validate([
            'schema' => 'required|array',
            'schema.sections' => 'required|array',
            'instruction' => 'required|string|max:1000',
        ]);

        return $this->respond($request, fn (?callable $progress) => $ai->editSchema(
            $request->schema,
            $request->instruction,
            $request->user()->id,
            $progress,
        ));
    }

    /** Apply an instruction to a single section. */
    public function editSection(Request $request, AiWebsiteService $ai)
    {
        $request->validate([
            'section' => 'required|array',
            'section.type' => 'required|string',
            'theme' => 'required|array',
            'instruction' => 'required|string|max:1000',
        ]);

        return $this->respond($request, fn () => $ai->editSection(
            $request->section,
            $request->instruction,
            $request->theme,
            $request->user()->id,
        ), allowStream: false);
    }

    /** Design 4 navbar logo concepts (SVG previews). */
    public function logo(Request $request, AiWebsiteService $ai)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'brand' => 'required|string|max:60',
            'theme' => 'required|array',
            'background' => 'nullable|string|max:20',
            'instruction' => 'nullable|string|max:500',
        ]);
        $project = Project::findOrFail($data['project_id']);
        abort_if($project->user_id !== $request->user()->id, 403);

        $theme = $data['theme'];
        $context = trim(($project->name ?? '') . '. ' . ($project->description ?? ''));

        return $this->respond($request, fn () => $ai->logoConcepts(
            brand: $data['brand'],
            theme: $theme,
            background: $data['background'] ?? ($theme['backgroundColor'] ?? '#ffffff'),
            instruction: $data['instruction'] ?? '',
            context: $context,
        ), allowStream: false);
    }

    /**
     * Save a chosen logo. The SVG is re-rendered on the server from the validated
     * spec — SVG markup from the browser is never stored.
     */
    public function saveLogo(Request $request, Project $project, LogoDesigner $designer, LogoRenderer $renderer)
    {
        abort_if($project->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'spec' => 'required|array',
            'brand' => 'required|string|max:60',
            'theme' => 'required|array',
            'background' => 'nullable|string|max:20',
        ]);

        $spec = $designer->normalize($data['spec'], $data['brand'], $data['theme'], $data['background'] ?? '#ffffff');
        $svg = $renderer->render($spec, $data['brand']);

        $path = "assets/{$project->id}/logo-" . substr(md5($svg), 0, 12) . '.svg';
        Storage::disk('public')->put($path, $svg);

        $asset = $project->assets()->create([
            'url' => '/storage/' . $path,
            'type' => 'image',
            'meta' => ['original_name' => 'AI logo — ' . $spec['name'] . '.svg', 'size' => strlen($svg), 'mime' => 'image/svg+xml', 'logo' => $spec],
        ]);

        return response()->json([
            'url' => $asset->url,
            // Logos with the name in them make the navbar text redundant.
            'containsName' => in_array($spec['layout'], ['icon-wordmark', 'monogram-wordmark', 'wordmark'], true),
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function respond(Request $request, callable $run, bool $allowStream = true)
    {
        // Large sites can take a while to write; don't let PHP's default limit cut them off.
        @set_time_limit(max(180, (int) config('ai.request_timeout') + 60));

        $wantsStream = $allowStream && str_contains((string) $request->header('Accept'), 'application/x-ndjson');

        if (!$wantsStream) {
            try {
                return response()->json($run(null));
            } catch (\Throwable $e) {
                [$message, $status] = $this->describe($e);

                return response()->json(['error' => $message], $status);
            }
        }

        return new StreamedResponse(function () use ($run) {
            $send = function (array $event) {
                echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            try {
                $result = $run(fn (array $progress) => $send(['event' => 'progress'] + $progress));
                $send(['event' => 'done'] + $result);
            } catch (\Throwable $e) {
                [$message, $status] = $this->describe($e);
                $send(['event' => 'error', 'error' => $message, 'status' => $status]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no', // stream through nginx without buffering
        ]);
    }

    /** @return array{0: string, 1: int} user-safe message and HTTP status */
    private function describe(\Throwable $e): array
    {
        if ($e instanceof AiException) {
            return [$e->getMessage(), $e->status];
        }

        Log::error('AI request failed', ['exception' => $e]);

        return ['Something went wrong while talking to the AI. Please try again.', 500];
    }

    private function saveWithVersion(Project $project, array $schema, string $label, int $userId): void
    {
        $page = Page::where('project_id', $project->id)->where('is_home', true)->first();
        if (!$page) {
            return;
        }

        // Keep the previous state restorable — but only if it had content.
        if (!empty($page->schema['sections'])) {
            PageVersion::create([
                'page_id' => $page->id,
                'schema' => $page->schema,
                'label' => $label,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        $page->update(['schema' => $schema]);
    }
}
