<?php

declare(strict_types=1);

namespace App\Mcp\Auth;

use App\Mcp\Exceptions\InvalidMcpToken;
use App\Models\McpToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class McpAuthenticator
{
    /**
     * Authenticate the Authorization bearer token and return an MCP context.
     *
     * The MCP layer intentionally does not rely on the Laravel web session.
     */
    public function authenticate(Request $request): McpContext
    {
        $rawToken = $request->bearerToken();

        if ($rawToken === null || $rawToken === '') {
            throw new InvalidMcpToken('Missing Authorization header with a Bearer token.');
        }

        if (! $this->hasValidFormat($rawToken)) {
            throw new InvalidMcpToken('Malformed MCP token format.');
        }

        $record = McpToken::query()->where('token_hash', hash('sha256', $rawToken))->first();

        if ($record === null) {
            throw new InvalidMcpToken('Unknown MCP token.');
        }

        if ($record->isRevoked()) {
            throw new InvalidMcpToken('This MCP token has been revoked.');
        }

        if ($record->isExpired()) {
            throw new InvalidMcpToken('This MCP token has expired.');
        }

        $record->forceFill(['last_used_at' => now()])->save();

        return new McpContext(
            token: $record,
            scopes: $record->scopeEnumValues(),
            requestId: $this->requestId($request),
        );
    }

    public function hasValidFormat(string $rawToken): bool
    {
        if ($rawToken === '') {
            return false;
        }

        if (! Str::startsWith($rawToken, (string) config('mcp.token_prefix', 'mcp_'))) {
            return false;
        }

        return Str::length($rawToken) >= ((int) Str::length((string) config('mcp.token_prefix', 'mcp_')) + 16);
    }

    private function requestId(Request $request): string
    {
        $header = $request->header('X-Request-ID');

        if (is_string($header) && $header !== '') {
            return Str::substr($header, 0, 100);
        }

        return (string) Str::uuid();
    }
}
