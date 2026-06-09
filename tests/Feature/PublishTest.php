<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\User;

function createProjectWithPage(User $user, string $status = 'draft'): Project
{
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Publish Test',
        'slug' => 'publish-test-abc123',
        'status' => $status,
    ]);

    Page::create([
        'project_id' => $project->id,
        'title' => 'Home',
        'path' => '/',
        'schema' => [
            'version' => 1,
            'meta' => ['title' => 'Publish Test', 'description' => 'Test', 'language' => 'en'],
            'theme' => [
                'primaryColor' => '#2563eb', 'secondaryColor' => '#0f172a',
                'backgroundColor' => '#ffffff', 'textColor' => '#0f172a',
                'fontHeading' => 'Poppins', 'fontBody' => 'Inter',
                'radius' => 'lg', 'mode' => 'light',
            ],
            'sections' => [
                ['id' => 'blk_hero_01', 'type' => 'hero', 'props' => ['heading' => 'Hello']],
            ],
        ],
        'is_home' => true,
    ]);

    return $project;
}

it('allows user to publish their project', function () {
    $user = User::factory()->create();
    $project = createProjectWithPage($user);

    $response = $this->actingAs($user)->post("/projects/{$project->id}/publish");

    $response->assertRedirect();
    expect($project->fresh()->status)->toBe('published');
});

it('makes published site accessible at /s/{slug}', function () {
    $user = User::factory()->create();
    $project = createProjectWithPage($user);

    $this->actingAs($user)->post("/projects/{$project->id}/publish");

    $response = $this->get("/s/{$project->slug}");

    $response->assertStatus(200);
});

it('returns 404 for unpublished site at /s/{slug}', function () {
    $user = User::factory()->create();
    $project = createProjectWithPage($user);

    // Project is draft by default, so /s/{slug} should 404
    $response = $this->get("/s/{$project->slug}");

    $response->assertStatus(404);
});
