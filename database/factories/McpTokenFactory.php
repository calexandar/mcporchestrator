<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Mcp\Auth\McpScope;
use App\Models\McpToken;
use App\Models\McpTokenScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<McpToken>
 */
class McpTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $raw = config('mcp.token_prefix', 'mcp_').Str::random(40);

        return [
            'name' => fake()->words(2, true),
            'token_hash' => hash('sha256', $raw),
            'token_prefix' => Str::substr($raw, 0, 12),
            'created_by' => User::factory(),
            'expires_at' => null,
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    /**
     * Attach scope rows for the token. Defaults to every scope.
     *
     * @param  list<string>|null  $scopes
     */
    public function withScopes(?array $scopes = null): static
    {
        $scopes ??= McpScope::values();

        return $this->afterCreating(function (McpToken $token) use ($scopes): void {
            foreach ($scopes as $scope) {
                McpTokenScope::query()->create([
                    'mcp_token_id' => $token->id,
                    'scope' => $scope,
                ]);
            }

            $token->unsetRelation('scopes');
        });
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'revoked_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
