<?php

use App\Models\Note;
use App\Models\Project;
use App\Models\Task;

test('projects:list filters by status when requested', function () {
    $active = Project::factory()->create();
    Project::factory()->archived()->create();
    $raw = mcpToken(['project:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:list', 'arguments' => ['status' => 'active']],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonCount(1, 'result.structuredContent.projects')
        ->assertJsonPath('result.structuredContent.projects.0.id', $active->id);
});

test('projects:get returns a single project', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['project:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:get', 'arguments' => ['project_id' => $project->id]],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.structuredContent.project.id', $project->id)
        ->assertJsonPath('result.structuredContent.project.name', $project->name);
});

test('projects:get returns a tool error for an unknown project', function () {
    $raw = mcpToken(['project:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:get', 'arguments' => ['project_id' => 9999]],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.structuredContent.error.code', 'resource_not_found');
});

test('tasks:list filters by project and status', function () {
    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->id]);
    Task::factory()->create();
    $raw = mcpToken(['task:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'tasks:list', 'arguments' => ['project_id' => $project->id]],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonCount(1, 'result.structuredContent.tasks');
});

test('tasks:get returns a task with optional notes', function () {
    $task = Task::factory()->create();
    Note::factory()->create(['task_id' => $task->id]);
    $raw = mcpToken(['task:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'tasks:get', 'arguments' => ['task_id' => $task->id, 'include_notes' => true]],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.structuredContent.task.id', $task->id)
        ->assertJsonCount(1, 'result.structuredContent.task.notes');
});

test('notes:list returns notes for a project', function () {
    $project = Project::factory()->create();
    $note = Note::factory()->for($project)->create();
    $raw = mcpToken(['note:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'notes:list', 'arguments' => ['project_id' => $project->id]],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.structuredContent.notes.0.id', $note->id);
});
