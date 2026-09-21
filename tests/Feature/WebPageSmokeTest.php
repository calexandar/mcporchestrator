<?php

use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDraft;
use App\Models\User;

test('guests are redirected from projects and tasks', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->get(route('tasks.index'))->assertRedirect(route('login'));

    $this->get(route('projects.show', Project::factory()->create()))->assertRedirect(route('login'));
    $this->get(route('tasks.show', Task::factory()->create()))->assertRedirect(route('login'));
});

test('projects index lists projects with counts', function () {
    $project = Project::factory()->create();
    Task::factory()->count(2)->create(['project_id' => $project->id]);
    Note::factory()->count(1)->create(['project_id' => $project->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(function ($page) use ($project) {
            $page->component('projects/Index')
                ->has('projects', 1)
                ->where('projects.0.id', $project->id)
                ->where('projects.0.tasks_count', 2)
                ->where('projects.0.notes_count', 1);
        });
});

test('project detail shows its tasks', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('projects/Show')->has('tasks', 1));
});

test('tasks index lists tasks with their project', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('tasks/Index')->has('tasks', 1));
});

test('task detail shows its notes', function () {
    $task = Task::factory()->create();
    Note::factory()->create(['task_id' => $task->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('tasks.show', $task))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('tasks/Show')->has('notes', 1));
});

test('dashboard passes stats to the page', function () {
    Project::factory()->create();
    Task::factory()->create();
    TaskDraft::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.open_tasks', 1)
            ->where('stats.pending_drafts', 1));
});
