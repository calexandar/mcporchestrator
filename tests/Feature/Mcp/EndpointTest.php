<?php

test('the MCP endpoint only accepts POST', function () {
    $this->getJson('/api/mcp')
        ->assertStatus(405)
        ->assertHeader('Allow', 'POST');

    $this->deleteJson('/api/mcp')
        ->assertStatus(405)
        ->assertHeader('Allow', 'POST');
});

test('a request without a bearer token is rejected', function () {
    $this->postJson('/api/mcp', [
        'id' => 1,
        'jsonrpc' => '2.0',
        'method' => 'ping',
    ])->assertStatus(401)
        ->assertJsonPath('error.code', 'invalid_mcp_token');
});

test('a valid token can call scope-free methods', function () {
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'id' => 7,
        'jsonrpc' => '2.0',
        'method' => 'ping',
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertOk()
        ->assertJsonPath('result.resultType', 'complete');
});

test('disabling the MCP endpoint returns 404', function () {
    config(['mcp.enabled' => false]);
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'id' => 1,
        'jsonrpc' => '2.0',
        'method' => 'ping',
    ], ['Authorization' => 'Bearer '.$raw])->assertNotFound();
});

test('an unlisted origin is rejected before dispatch', function () {
    config(['mcp.allowed_origins' => ['https://app.example.com']]);
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'id' => 1,
        'jsonrpc' => '2.0',
        'method' => 'ping',
    ], [
        'Authorization' => 'Bearer '.$raw,
        'Origin' => 'https://evil.example.org',
    ])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'origin_not_allowed');
});

test('an allowed origin is accepted', function () {
    config(['mcp.allowed_origins' => ['https://app.example.com']]);
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'id' => 1,
        'jsonrpc' => '2.0',
        'method' => 'ping',
    ], [
        'Authorization' => 'Bearer '.$raw,
        'Origin' => 'https://app.example.com',
    ])->assertOk();
});

test('malformed JSON returns a parse error', function () {
    $raw = mcpToken();

    $this->call('POST', '/api/mcp', server: [
        'HTTP_AUTHORIZATION' => 'Bearer '.$raw,
    ], content: '{not valid json')
        ->assertStatus(400)
        ->assertJsonPath('error.code', -32700);
});

test('an empty request body is an invalid request', function () {
    $raw = mcpToken();

    $this->postJson('/api/mcp', [], ['Authorization' => 'Bearer '.$raw])
        ->assertStatus(400)
        ->assertJsonPath('error.code', -32600);
});

test('unknown methods resolve to method not found', function () {
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        'id' => 5,
        'jsonrpc' => '2.0',
        'method' => 'unknown/thing',
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertNotFound()
        ->assertJsonPath('error.code', -32601);
});

test('batched requests are rejected by the single-message transport', function () {
    $raw = mcpToken();

    $this->postJson('/api/mcp', [
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
        ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'nope'],
        ['jsonrpc' => '2.0', 'method' => 'notifications/foo'],
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertStatus(400)
        ->assertJsonPath('error.code', -32600);
});

test('rate limiting kicks in past the configured limit', function () {
    config(['mcp.rate_limit' => 2, 'mcp.rate_limit_decay_minutes' => 1]);
    $raw = mcpToken();

    foreach ([1, 2] as $id) {
        $this->postJson('/api/mcp', [
            'id' => $id,
            'jsonrpc' => '2.0',
            'method' => 'ping',
        ], ['Authorization' => 'Bearer '.$raw])->assertOk();
    }

    $this->postJson('/api/mcp', [
        'id' => 3,
        'jsonrpc' => '2.0',
        'method' => 'ping',
    ], ['Authorization' => 'Bearer '.$raw])
        ->assertStatus(429);
});
