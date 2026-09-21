<?php

test('initialize negotiates a supported protocol version', function () {
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'tester']],
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertOk()
        ->assertJsonPath('result.protocolVersion', '2025-06-18')
        ->assertJsonPath('result.capabilities.tools.listChanged', false)
        ->assertJsonPath('result.serverInfo.name', config('mcp.server_name'))
        ->assertJsonPath('result.serverInfo.version', config('mcp.server_version'));
});

test('initialize falls back to the latest version for unknown protocols', function () {
    $raw = mcpToken();

    $response = $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => ['protocolVersion' => '2099-01-01'],
    ], ['Authorization' => 'Bearer '.$raw]);

    $response->assertOk()
        ->assertJsonPath('result.protocolVersion', '2025-11-25');
});

test('notifications are answered with an empty 202', function () {
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized',
        'params' => [],
    ], ['Authorization' => 'Bearer '.$raw])->assertStatus(202);
});
