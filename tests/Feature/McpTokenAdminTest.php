<?php

use App\Mcp\Auth\McpScope;
use App\Models\McpToken;
use App\Models\User;

test('guests cannot manage tokens', function () {
    $this->get(route('mcp.tokens'))->assertRedirect(route('login'));
});

test('authenticated users can create a token and see it in the list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('mcp.tokens'))
        ->post(route('mcp.tokens.store'), [
            'name' => 'ci-client',
            'scopes' => ['project:read', 'task:read'],
            'days' => 90,
        ]);

    $response->assertRedirect(route('mcp.tokens'))
        ->assertSessionHas('mcp_new_token');

    $this->assertDatabaseHas('mcp_tokens', [
        'name' => 'ci-client',
        'created_by' => $user->id,
    ]);

    $token = McpToken::query()->where('name', 'ci-client')->first();
    $this->assertDatabaseHas('mcp_token_scopes', ['mcp_token_id' => $token->id, 'scope' => 'project:read']);

    $this->actingAs($user)
        ->get(route('mcp.tokens'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mcp/Tokens')
            ->where('scopes', McpScope::values()));
});

test('token creation accepts the JSON payload the web form sends', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('mcp.tokens'))
        ->postJson(route('mcp.tokens.store'), [
            'name' => 'json-client',
            'scopes' => ['task:read'],
            'days' => '90',
        ])
        ->assertRedirect(route('mcp.tokens'))
        ->assertSessionHas('mcp_new_token')
        ->assertSessionHasNoErrors();
});

test('token creation validates scopes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('mcp.tokens.store'), ['name' => 'bad', 'scopes' => [], 'days' => 90])
        ->assertSessionHasErrors('scopes');

    $this->actingAs($user)
        ->post(route('mcp.tokens.store'), ['name' => 'bad', 'scopes' => ['nope:write']])
        ->assertSessionHasErrors('scopes.0');
});

test('revoking a token marks it as revoked and cannot authenticate thereafter', function () {
    $user = User::factory()->create();
    $token = McpToken::factory()->withScopes(McpScope::values())->create();

    $this->actingAs($user)
        ->from(route('mcp.tokens'))
        ->patch(route('mcp.tokens.revoke', $token))
        ->assertRedirect(route('mcp.tokens'));

    expect($token->refresh()->revoked_at)->not->toBeNull();
});

test('the raw token is only returned once during creation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('mcp.tokens'))
        ->post(route('mcp.tokens.store'), ['name' => 'one-shot', 'scopes' => ['note:read']])
        ->assertSessionHas('mcp_new_token');

    $this->actingAs($user)
        ->get(route('mcp.tokens'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('newToken'));
});
