<?php

use App\Models\Project;
use App\Models\User;

it('allows authenticated user to create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'name' => 'My Website',
        'description' => 'A test project',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'My Website',
    ]);
});

it('creates a home page when a project is created', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', [
        'name' => 'My Website',
    ]);

    $project = Project::where('user_id', $user->id)->first();

    expect($project)->not->toBeNull();
    $this->assertDatabaseHas('pages', [
        'project_id' => $project->id,
        'is_home' => true,
        'title' => 'Home',
    ]);
});

it('allows authenticated user to view dashboard with projects', function () {
    $user = User::factory()->create();

    Project::create([
        'user_id' => $user->id,
        'name' => 'Dashboard Project',
        'slug' => 'dashboard-project-abc123',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});

it('allows authenticated user to delete own project', function () {
    $user = User::factory()->create();

    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Delete Me',
        'slug' => 'delete-me-abc123',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user)->delete("/projects/{$project->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

it('prevents user from accessing another users project editor', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::create([
        'user_id' => $owner->id,
        'name' => 'Private Project',
        'slug' => 'private-project-abc123',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($other)->get("/projects/{$project->id}/editor");

    $response->assertStatus(403);
});
