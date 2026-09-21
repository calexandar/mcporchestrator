<?php

use App\Models\McpAuditLog;
use App\Models\McpToken;
use App\Models\Project;

test('successful tool calls are recorded in the audit log', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['project:read']);

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:get', 'arguments' => ['project_id' => $project->id]],
    ], ['Authorization' => 'Bearer '.$raw])->assertOk();

    $entry = McpAuditLog::query()
        ->where('tool_name', 'projects:get')
        ->with('token')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->success)->toBeTrue()
        ->and($entry->token->name)->toBe('test-token')
        ->and($entry->input_hash)->not->toBeNull()
        ->and($entry->metadata)->toHaveKey('duration_ms')
        ->and($entry->metadata)->toHaveKey('request_id');
});

test('failed tool calls are recorded with their error code', function () {
    $raw = mcpToken(['project:read']);

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:get', 'arguments' => ['project_id' => 9999]],
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertOk()
        ->assertJsonPath('result.isError', true);

    $entry = McpAuditLog::query()->where('tool_name', 'projects:get')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->success)->toBeFalse()
        ->and($entry->error_code)->toBe('resource_not_found');
});

test('scope denials and unknown tools are not audited as tool calls', function () {
    $raw = mcpToken(['note:read']);
    $token = McpToken::query()->where('token_prefix', substr($raw, 0, 12))->first();

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:list', 'arguments' => []],
    ], ['Authorization' => 'Bearer '.$raw])->assertStatus(400);

    expect(McpAuditLog::query()->where('mcp_token_id', $token->id)->count())->toBe(0);
});

test('actions requiring a write scope record the scope in the audit row', function () {
    $project = Project::factory()->create();
    $raw = mcpToken(['task:draft:write']);

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tasks:create-draft',
            'arguments' => ['operation_id' => 'op-1', 'project_id' => $project->id, 'title' => 'T'],
        ],
    ], ['Authorization' => 'Bearer '.$raw])->assertOk();

    $entry = McpAuditLog::query()->where('tool_name', 'tasks:create-draft')->first();

    expect($entry->scope)->toBe('task:draft:write');
});
