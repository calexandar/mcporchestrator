<?php

declare(strict_types=1);

namespace App\Mcp\Auth;

use App\Models\McpToken;

final readonly class McpContext
{
    /**
     * @param  list<McpScope>  $scopes
     */
    public function __construct(
        public McpToken $token,
        public array $scopes,
        public string $requestId,
    ) {}

    public function hasScope(McpScope $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Safe representation of the context. Never includes the token hash.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token_id' => $this->token->id,
            'token_name' => $this->token->name,
            'scopes' => array_map(fn (McpScope $scope): string => $scope->value, $this->scopes),
            'request_id' => $this->requestId,
        ];
    }
}
