<?php

use App\Mcp\Auth\McpScope;
use App\Mcp\Exceptions\McpValidationFailed;
use App\Mcp\Support\McpInput;
use App\Mcp\Support\McpTokenService;
use App\Models\McpToken;
use InvalidArgumentException;

test('scope values cover the full permission set', function () {
    expect(McpScope::values())->toBe([
        'project:read',
        'task:read',
        'task:draft:write',
        'note:read',
        'note:write',
    ]);
});

test('input validation rejects unknown properties', function () {
    $input = McpInput::make([
        'title' => 'Hello',
        'surprise' => 42,
    ]);

    $input->string('title', required: true, max: 255);

    expect(fn () => $input->result())->toThrow(McpValidationFailed::class);
});

test('input validation enforces max lengths', function () {
    $input = McpInput::make(['title' => str_repeat('a', 300)]);

    $input->string('title', required: true, max: 255);

    expect(fn () => $input->result())->toThrow(McpValidationFailed::class);
});

test('oneOf restricts to allowed enum values', function () {
    $input = McpInput::make(['priority' => 'urgent']);

    $input->oneOf('priority', ['low', 'medium', 'high']);

    expect(fn () => $input->result())->toThrow(McpValidationFailed::class);
});

test('token generation produces a hashed token with scopes', function () {
    $result = app(McpTokenService::class)->generate(
        name: 'unit',
        scopes: ['project:read'],
        expiresInDays: 30,
        createdBy: null,
    );

    expect(hash('sha256', $result['raw']))->toBe($result['token']->token_hash)
        ->and($result['token']->token_prefix)->toBe(substr($result['raw'], 0, 12))
        ->and($result['token']->expires_at)->not->toBeNull()
        ->and($result['token']->scopeEnumValues())->toBe([McpScope::ProjectRead]);
});

test('token generation rejects unknown scopes', function () {
    expect(fn () => app(McpTokenService::class)->generate('x', ['bogus:read'], null, null))
        ->toThrow(InvalidArgumentException::class);
});

test('token generation rejects an empty scope set', function () {
    expect(fn () => app(McpTokenService::class)->generate('x', [], null, null))
        ->toThrow(InvalidArgumentException::class);
});

test('revoking a token is idempotent', function () {
    $token = McpToken::factory()->create();
    $service = app(McpTokenService::class);

    $service->revoke($token);
    $service->revoke($token);

    expect($token->refresh()->revoked_at)->not->toBeNull();
});
