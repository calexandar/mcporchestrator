<?php

declare(strict_types=1);

namespace App\Mcp\Audit;

use App\Mcp\Auth\McpContext;
use App\Mcp\Auth\McpScope;
use App\Models\McpAuditLog;

final class McpAuditor
{
    /**
     * Record a single tool invocation in the audit log.
     *
     * Only safe metadata is persisted: never the raw token, request body, or
     * arguments. The arguments are reduced to a SHA-256 hash for correlation.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function recordToolCall(
        McpContext $context,
        string $toolName,
        McpScope $requiredScope,
        array $arguments,
        int $startedAtNanos,
        bool $success,
        ?string $errorCode = null,
        ?string $operationId = null,
    ): void {
        $durationMs = (int) round((hrtime(true) - $startedAtNanos) / 1_000_000);

        McpAuditLog::query()->create([
            'mcp_token_id' => $context->token->id,
            'operation_id' => $operationId,
            'tool_name' => $toolName,
            'action' => $success ? 'tools/call' : 'tools/call:error',
            'scope' => $requiredScope->value,
            'input_hash' => hash('sha256', json_encode($arguments, JSON_THROW_ON_ERROR)),
            'success' => $success,
            'error_code' => $errorCode,
            'metadata' => [
                'duration_ms' => $durationMs,
                'request_id' => $context->requestId,
            ],
            'created_at' => now(),
        ]);
    }
}
