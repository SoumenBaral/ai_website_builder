<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageVersion;
use App\Services\AiWebsiteService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function generate(Request $request, AiWebsiteService $ai)
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        try {
            $schema = $ai->generate(
                $request->prompt,
                $request->user()->id,
                $request->project_id,
            );

            if ($request->project_id) {
                $page = Page::where('project_id', $request->project_id)
                    ->where('is_home', true)
                    ->first();

                if ($page) {
                    // Save current state as a version before overwriting
                    if ($page->schema) {
                        PageVersion::create([
                            'page_id' => $page->id,
                            'schema' => $page->schema,
                            'label' => 'Before AI generation',
                            'created_by' => $request->user()->id,
                            'created_at' => now(),
                        ]);
                    }
                    $page->update(['schema' => $schema]);
                }
            }

            return response()->json(['schema' => $schema]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function editBlock(Request $request, AiWebsiteService $ai)
    {
        $request->validate([
            'schema' => 'required|array',
            'instruction' => 'required|string|max:1000',
        ]);

        try {
            $schema = $ai->editSchema(
                $request->schema,
                $request->instruction,
                $request->user()->id,
            );

            return response()->json(['schema' => $schema]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function editSection(Request $request, AiWebsiteService $ai)
    {
        $request->validate([
            'section' => 'required|array',
            'theme' => 'required|array',
            'instruction' => 'required|string|max:1000',
        ]);

        try {
            $section = $ai->editSection(
                $request->section,
                $request->instruction,
                $request->theme,
                $request->user()->id,
            );

            return response()->json(['section' => $section]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
