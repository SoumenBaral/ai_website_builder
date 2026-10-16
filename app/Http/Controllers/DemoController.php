<?php

namespace App\Http\Controllers;

use App\Services\DemoSite;
use App\Services\ExportService;

/** The public showcase at /demo — a real published-site render of a hand-made demo shop. */
class DemoController extends Controller
{
    private const SHOP = ['mode' => 'demo', 'key' => 'aiwb-demo'];

    public function show(ExportService $export)
    {
        $html = $export->toHtml(DemoSite::schema(), null, null, null, $this->itemUrl(), self::SHOP);

        return $this->respond($html);
    }

    public function item(string $section, string $item, ExportService $export)
    {
        $html = $export->itemHtml(DemoSite::schema(), $section, $item, route('demo'), $this->itemUrl(), null, null, null, self::SHOP);
        abort_if($html === null, 404);

        return $this->respond($html);
    }

    private function itemUrl(): \Closure
    {
        return fn (string $anchor, string $slug) => route('demo.item', [$anchor, $slug]);
    }

    /** Adds the "Built with AiWebBuilder" pill. */
    private function respond(string $html)
    {
        $pill = view('export.partials.demo-pill', ['signup' => route('register'), 'home' => url('/')])->render();

        return response(str_replace('</body>', $pill . '</body>', $html))->header('Content-Type', 'text/html');
    }
}
