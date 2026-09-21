<?php

use App\Mcp\Support\McpTokenService;
use App\Models\Project;
use App\Models\User;

test('guests are redirected from the audit log page', function () {
    $this->get(route('mcp.audit-log'))->assertRedirect(route('login'));
});

test('the audit log page lists recorded tool calls for authenticated users', function () {
    $raw = app(McpTokenService::class)->generate(
        name: 'ci',
        scopes: ['project:read'],
        expiresInDays: null,
        createdBy: null,
    )['raw'];

    $project = Project::factory()->create();

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:get', 'arguments' => ['project_id' => $project->id]],
    ], ['Authorization' => 'Bearer '.$raw])->assertOk();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mcp.audit-log'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $page->component('mcp/AuditLog')
                ->has('entries', 1)
                ->where('entries.0.tool_name', 'projects:get')
                ->where('entries.0.success', true);
        });
});

test('the audit log supports success filtering', function () {
    $raw = app(McpTokenService::class)->generate(
        name: 'ci',
        scopes: ['project:read'],
        expiresInDays: null,
        createdBy: null,
    )['raw'];

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'projects:get', 'arguments' => ['project_id' => 9999]],
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertOk()
        ->assertJsonPath('result.isError', true);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mcp.audit-log', ['success' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('entries', 0));
});
