<?php

use App\Models\Project;
use App\Models\TaskDraft;
use Illuminate\Support\Str;

test('tasks:create-draft creates a pending draft and returns it', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['task:draft:write']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tasks:create-draft',
            'arguments' => [
                'operation_id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'title' => 'Build the audit trail',
                'description' => 'Record every tool call.',
                'priority' => 'high',
            ],
        ],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.draft.status', 'draft')
        ->assertJsonPath('result.structuredContent.draft.title', 'Build the audit trail');

    $this->assertDatabaseHas('task_drafts', [
        'project_id' => $project->id,
        'title' => 'Build the audit trail',
        'status' => 'draft',
    ]);
});

test('tasks:create-draft is idempotent per operation id', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['task:draft:write']);
    $operationId = (string) Str::uuid();

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tasks:create-draft',
            'arguments' => [
                'operation_id' => $operationId,
                'project_id' => $project->id,
                'title' => 'Retry me',
            ],
        ],
    ];

    $first = $this->postJson('/api/mcp', $payload, ['Authorization' => 'Bearer '.$raw]);
    $second = $this->postJson('/api/mcp', $payload, ['Authorization' => 'Bearer '.$raw]);

    $firstId = $first->json('result.structuredContent.draft.id');
    $secondId = $second->json('result.structuredContent.draft.id');

    expect($secondId)->toBe($firstId);
    $this->assertDatabaseCount('task_drafts', 1);
});

test('tasks:create-draft validates its input', function () {
    $raw = mcpToken(['task:draft:write']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'tasks:create-draft', 'arguments' => ['operation_id' => 'missing everything']],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.structuredContent.error.code', 'validation_failed');
});

test('unknown tools are rejected as tool not found', function () {
    $raw = mcpToken();

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'tasks:explode', 'arguments' => []],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', -32602);
});

test('tasks:update-draft applies changes with optimistic concurrency', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['task:draft:write']);
    $draft = TaskDraft::factory()->create(['project_id' => $project->id]);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tasks:update-draft',
            'arguments' => [
                'operation_id' => (string) Str::uuid(),
                'draft_id' => $draft->id,
                'expected_updated_at' => $draft->updated_at->toIso8601String(),
                'title' => 'Updated title',
            ],
        ],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.structuredContent.draft.title', 'Updated title');
    $this->assertDatabaseHas('task_drafts', ['id' => $draft->id, 'title' => 'Updated title']);
});

test('tasks:update-draft rejects stale reads with a conflict', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['task:draft:write']);
    $draft = TaskDraft::factory()->create(['project_id' => $project->id]);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tasks:update-draft',
            'arguments' => [
                'operation_id' => (string) Str::uuid(),
                'draft_id' => $draft->id,
                'expected_updated_at' => now()->subDay()->toIso8601String(),
                'title' => 'Should not apply',
            ],
        ],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.structuredContent.error.code', 'stale_resource');
    $this->assertDatabaseMissing('task_drafts', ['id' => $draft->id, 'title' => 'Should not apply']);
});

test('notes:create creates a note and is idempotent', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['note:write']);
    $operationId = (string) Str::uuid();

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'notes:create',
            'arguments' => [
                'operation_id' => $operationId,
                'project_id' => $project->id,
                'title' => 'Meeting notes',
                'body' => 'Discussed the roadmap.',
            ],
        ],
    ];

    $first = $this->postJson('/api/mcp', $payload, ['Authorization' => 'Bearer '.$raw]);
    $second = $this->postJson('/api/mcp', $payload, ['Authorization' => 'Bearer '.$raw]);

    $firstId = $first->json('result.structuredContent.note.id');
    $secondId = $second->json('result.structuredContent.note.id');

    expect($secondId)->toBe($firstId);
    $this->assertDatabaseCount('notes', 1);
});
