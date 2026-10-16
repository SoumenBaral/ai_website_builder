<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['ai.font_path' => base_path('tests/Fixtures/fonts')]);
    Http::fake(['*' => Http::response('', 404)]);
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->project = Project::create(['user_id' => $this->user->id, 'name' => 'Rohit Sharma', 'slug' => 'rohit-abc', 'status' => 'published']);
    $this->page = Page::create(['project_id' => $this->project->id, 'title' => 'Home', 'path' => '/', 'is_home' => true, 'schema' => [
        'version' => 1, 'meta' => ['title' => 'Rohit Sharma | Cricket', 'description' => '', 'language' => 'en'],
        'theme' => ['primaryColor' => '#6366f1', 'secondaryColor' => '#0b1220', 'backgroundColor' => '#0b1220', 'textColor' => '#e5e7eb', 'fontHeading' => 'Oswald', 'fontBody' => 'Inter', 'radius' => 'lg', 'mode' => 'dark'],
        'sections' => [['id' => 'n', 'type' => 'navbar', 'props' => ['brand' => 'Rohit Sharma', 'links' => []]]],
    ]]);
    $this->spec = ['name' => 'Crest', 'layout' => 'icon-wordmark', 'monogram' => 'RS', 'icon' => 'trophy', 'shape' => 'shield', 'markStyle' => 'solid', 'font' => 'Oswald', 'weight' => '700', 'case' => 'upper', 'tracking' => 'wide', 'accent' => 'none', 'markColor' => '#6366f1', 'textColor' => '#ffffff', 'accentColor' => '#6366f1'];
});

function file_of(array $favicon, string $name): string
{
    return Storage::disk('public')->get(ltrim(str_replace('/storage/', '', $favicon['files'][$name]), '/'));
}

function solid_png(int $size, int $r, int $g, int $b): string
{
    $img = imagecreatetruecolor($size, $size);
    imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
    ob_start();
    imagepng($img);

    return (string) ob_get_clean();
}

function alpha_at(string $png, int $x, int $y): int
{
    $img = imagecreatefromstring($png);

    return (imagecolorat($img, $x, $y) >> 24) & 0x7F; // 0 = opaque, 127 = transparent
}

it('creates a complete favicon set when an AI logo is saved', function () {
    $response = $this->actingAs($this->user)->postJson(route('projects.logo', $this->project), [
        'spec' => $this->spec, 'brand' => 'Rohit Sharma', 'theme' => $this->page->schema['theme'], 'background' => '#0b1220',
    ])->assertOk();

    $favicon = $response->json('favicon');
    expect(array_keys($favicon['files']))->toEqualCanonicalizing(['favicon.svg', 'favicon.ico', 'favicon-32.png', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'manifest.json'])
        ->and($favicon['followsLogo'])->toBeTrue()
        ->and($favicon['sourceUrl'])->toBe($response->json('url'))
        ->and($this->project->fresh()->favicon['dir'])->toBe($favicon['dir']);

    foreach (['favicon-32.png' => 32, 'apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512] as $name => $size) {
        expect(getimagesizefromstring(file_of($favicon, $name))[0])->toBe($size);
    }

    // Tab icon: shield on transparent corners. App icons: opaque full-bleed (iOS shows transparency as black).
    expect(alpha_at(file_of($favicon, 'favicon-32.png'), 0, 31))->toBe(127)
        ->and(alpha_at(file_of($favicon, 'apple-touch-icon.png'), 0, 0))->toBe(0)
        ->and(alpha_at(file_of($favicon, 'icon-512.png'), 2, 2))->toBe(0);

    $ico = file_of($favicon, 'favicon.ico');
    expect(unpack('vreserved/vtype/vcount', substr($ico, 0, 6)))->toBe(['reserved' => 0, 'type' => 1, 'count' => 3]);

    $manifest = json_decode(file_of($favicon, 'manifest.json'), true);
    expect($manifest['short_name'])->toBe('Rohit Sharma')
        ->and($manifest['icons'][1])->toMatchArray(['sizes' => '512x512', 'purpose' => 'any maskable']);
});

it('keeps a custom favicon when the logo changes later', function () {
    Storage::disk('public')->put('assets/' . $this->project->id . '/icon.png', solid_png(64, 200, 30, 30));
    $this->project->assets()->create(['url' => '/storage/assets/' . $this->project->id . '/icon.png', 'type' => 'image', 'meta' => []]);

    $this->actingAs($this->user)->postJson(route('projects.favicon', $this->project), ['url' => '/storage/assets/' . $this->project->id . '/icon.png', 'followsLogo' => false])
        ->assertOk()->assertJsonPath('favicon.source', 'custom');

    $response = $this->actingAs($this->user)->postJson(route('projects.logo', $this->project), ['spec' => $this->spec, 'brand' => 'Rohit Sharma', 'theme' => $this->page->schema['theme']]);

    expect($response->json('favicon.source'))->toBe('custom')
        ->and($this->project->fresh()->favicon['sourceUrl'])->toEndWith('icon.png');
});

it('builds a favicon from an uploaded photo logo (cropped to a square)', function () {
    $img = imagecreatetruecolor(300, 200);
    imagefill($img, 0, 0, imagecolorallocate($img, 20, 120, 60));
    ob_start();
    imagejpeg($img);
    Storage::disk('public')->put('assets/' . $this->project->id . '/logo.jpg', ob_get_clean());
    $this->project->assets()->create(['url' => '/storage/assets/' . $this->project->id . '/logo.jpg', 'type' => 'image', 'meta' => []]);

    $favicon = $this->actingAs($this->user)
        ->postJson(route('projects.favicon', $this->project), ['url' => '/storage/assets/' . $this->project->id . '/logo.jpg'])
        ->assertOk()->json('favicon');

    expect($favicon['files'])->toHaveKeys(['favicon.ico', 'favicon-32.png', 'apple-touch-icon.png', 'icon-512.png'])
        ->and(getimagesizefromstring(file_of($favicon, 'apple-touch-icon.png'))[1])->toBe(180);
});

it('only accepts images from the same project and only for the owner', function () {
    $this->actingAs($this->user)->postJson(route('projects.favicon', $this->project), ['url' => 'https://evil.example/x.png'])->assertStatus(422);
    $this->actingAs(User::factory()->create())->postJson(route('projects.favicon', $this->project), ['url' => '/storage/x.png'])->assertForbidden();
});

it('adds favicon tags to the published page and ZIP export', function () {
    $this->actingAs($this->user)->postJson(route('projects.logo', $this->project), ['spec' => $this->spec, 'brand' => 'Rohit Sharma', 'theme' => $this->page->schema['theme']]);
    $project = $this->project->fresh();

    $html = $this->get('/s/' . $project->slug)->assertOk()->getContent();
    expect($html)->toContain('rel="icon" href="' . $project->favicon['files']['favicon.svg'] . '" type="image/svg+xml"')
        ->toContain('rel="apple-touch-icon"')
        ->toContain('rel="manifest"')
        ->toContain('<meta name="theme-color" content="#0b1220">');

    $zipPath = app(\App\Services\ExportService::class)->toZip($this->page->fresh()->schema, $project);
    $zip = new ZipArchive();
    $zip->open($zipPath);
    $names = array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));
    $index = $zip->getFromName('index.html');
    $manifest = json_decode($zip->getFromName('manifest.json'), true);
    $zip->close();
    @unlink($zipPath);

    expect($names)->toContain('favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'icon-512.png', 'manifest.json')
        ->and($index)->toContain('href="favicon.svg"')
        ->and($manifest['icons'][0]['src'])->toBe('icon-192.png');
});

it('uses an initials icon instead of the builder favicon when none is set, and can reset', function () {
    $html = $this->get('/s/' . $this->project->slug)->assertOk()->getContent();
    expect($html)->toContain('rel="icon" type="image/svg+xml" href="data:image/svg+xml,');

    $this->actingAs($this->user)->postJson(route('projects.logo', $this->project), ['spec' => $this->spec, 'brand' => 'Rohit Sharma', 'theme' => $this->page->schema['theme']]);
    $dir = $this->project->fresh()->favicon['dir'];
    $this->actingAs($this->user)->deleteJson(route('projects.favicon.destroy', $this->project))->assertOk();

    expect($this->project->fresh()->favicon)->toBeNull();
    Storage::disk('public')->assertMissing($dir . '/favicon.svg');
});
