<?php

use App\Mcp\Servers\LabServer;
use App\Mcp\Tools\Notes\ListNotesTool;
use App\Mcp\Tools\Projects\ListProjectsTool;
use App\Mcp\Tools\Tasks\CreateTaskDraftTool;
use App\Models\Project;
use App\Models\Task;

test('read-only tokens cannot call write tools', function () {
    $raw = mcpToken(['project:read']);
    $project = Project::factory()->create();

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tasks:create-draft',
            'arguments' => ['operation_id' => 'op-1', 'project_id' => $project->id, 'title' => 'Nope'],
        ],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', -32602);
});

test('a token missing the task:read scope cannot read tasks', function () {
    $task = Task::factory()->create();
    $raw = mcpToken(['project:read', 'note:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'tasks:get', 'arguments' => ['task_id' => $task->id]],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', -32602);
});

test('a token without note:write cannot call notes:create', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['note:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'notes:create',
            'arguments' => ['operation_id' => 'op-1', 'project_id' => $project->id, 'title' => 'T', 'body' => 'B'],
        ],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', -32602);
});

test('out-of-scope tools are not registered for the current context', function () {
    mcpToken(['note:read']);

    LabServer::tool(CreateTaskDraftTool::class, ['project_id' => 1, 'title' => 'Nope'])
        ->assertNotRegistered();

    LabServer::tool(ListProjectsTool::class)->assertNotRegistered();

    LabServer::tool(ListNotesTool::class)->assertOk();
});
