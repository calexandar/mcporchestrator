<?php

test('tools/list advertises every registered tool', function () {
    $raw = mcpToken();

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk();

    $names = collect($response->json('result.tools'))->pluck('name')->all();

    expect($names)->toMatchArray([
        'projects:list',
        'projects:get',
        'tasks:list',
        'tasks:get',
        'tasks:create-draft',
        'tasks:update-draft',
        'notes:list',
        'notes:create',
    ])->toHaveCount(8);
});

test('tools/list exposes input schemas with required fields', function () {
    $raw = mcpToken();

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], ['Authorization' => 'Bearer '.$raw]);

    $tool = collect($response->json('result.tools'))->firstWhere('name', 'tasks:create-draft');

    expect($tool['inputSchema']['required'])->toContain('operation_id', 'project_id', 'title');
});

test('tools/list only exposes tools within the granted scopes', function () {
    $raw = mcpToken(['note:read']);

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], ['Authorization' => 'Bearer '.$raw]);

    $names = collect($response->json('result.tools'))->pluck('name')->all();

    expect($names)->toBe(['notes:list']);
});
