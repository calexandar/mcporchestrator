<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Mcp\Auth\McpScope;
use App\Models\McpToken;
use App\Models\McpTokenScope;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class McpTokenService
{
    /**
     * Generate a new MCP token.
     *
     * @param  list<string>  $scopes  Raw scope strings, e.g. "project:read".
     * @return array{token: McpToken, raw: string}
     *
     * @throws InvalidArgumentException When an unknown scope is provided.
     */
    public function generate(string $name, array $scopes, ?int $expiresInDays, ?int $createdBy): array
    {
        $scopeEnums = $this->normalizeScopes($scopes);

        $raw = config('mcp.token_prefix', 'mcp_').Str::random(40);
        $tokenHash = hash('sha256', $raw);

        $token = McpToken::query()->create([
            'name' => $name,
            'token_hash' => $tokenHash,
            'token_prefix' => Str::substr($raw, 0, 12),
            'created_by' => $createdBy,
            'expires_at' => $expiresInDays !== null && $expiresInDays > 0
                ? now()->addDays($expiresInDays)
                : null,
        ]);

        foreach ($scopeEnums as $scope) {
            McpTokenScope::query()->create([
                'mcp_token_id' => $token->id,
                'scope' => $scope->value,
            ]);
        }

        return [
            'token' => $token,
            'raw' => $raw,
        ];
    }

    /**
     * @param  list<string>  $scopes
     * @return list<McpScope>
     */
    private function normalizeScopes(array $scopes): array
    {
        $known = McpScope::values();

        foreach ($scopes as $scope) {
            if (! in_array($scope, $known, true)) {
                throw new InvalidArgumentException("Unknown MCP scope: [{$scope}].");
            }
        }

        if ($scopes === []) {
            throw new InvalidArgumentException('At least one MCP scope is required.');
        }

        return array_map(fn (string $scope): McpScope => McpScope::from($scope), array_values(array_unique($scopes)));
    }

    public function revoke(McpToken $token): void
    {
        if ($token->revoked_at === null) {
            $token->forceFill(['revoked_at' => now()])->save();
        }
    }
}
