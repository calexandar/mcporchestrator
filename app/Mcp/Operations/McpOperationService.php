<?php

declare(strict_types=1);

namespace App\Mcp\Operations;

use App\Enums\McpOperationStatus;
use App\Mcp\Auth\McpContext;
use App\Mcp\Exceptions\McpException;
use App\Mcp\Exceptions\McpOperationConflict;
use App\Models\McpOperation;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class McpOperationService
{
    /**
     * Execute a mutating callback exactly once per (token, operation_id) pair.
     *
     * - Duplicate completed operations replay their stored result.
     * - Still-processing operations raise a conflict.
     * - Failed operations may be retried.
     *
     * @param  Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public function execute(McpContext $context, string $operationId, string $operation, Closure $callback): array
    {
        $existing = $this->find($context, $operationId);

        if ($existing !== null) {
            if ($existing->status === McpOperationStatus::Processing) {
                throw new McpOperationConflict('Operation is currently processing. Wait for it to complete before retrying.');
            }

            if ($existing->status === McpOperationStatus::Completed) {
                return is_array($existing->result) ? $existing->result : [];
            }
        }

        try {
            return DB::transaction(function () use ($context, $operationId, $operation, $existing, $callback): array {
                $record = $existing ?? new McpOperation;

                $record->fill([
                    'mcp_token_id' => $context->token->id,
                    'operation_id' => $operationId,
                    'operation' => $operation,
                    'status' => McpOperationStatus::Processing,
                    'result' => null,
                ])->save();

                $result = $callback();

                $record->forceFill([
                    'status' => McpOperationStatus::Completed,
                    'result' => $result,
                ])->save();

                return $result;
            });
        } catch (QueryException $queryException) {
            if ($this->isUniqueViolation($queryException)) {
                $concurrent = $this->find($context, $operationId);

                if ($concurrent?->status === McpOperationStatus::Processing) {
                    throw new McpOperationConflict('Operation is currently processing. Wait for it to complete before retrying.');
                }
            }

            throw $queryException;
        } catch (Throwable $exception) {
            $this->markFailed($context, $operationId, $operation, $exception);

            throw $exception;
        }
    }

    private function find(McpContext $context, string $operationId): ?McpOperation
    {
        return McpOperation::query()
            ->where('mcp_token_id', $context->token->id)
            ->where('operation_id', $operationId)
            ->first();
    }

    private function markFailed(McpContext $context, string $operationId, string $operation, Throwable $exception): void
    {
        try {
            McpOperation::query()->updateOrCreate(
                [
                    'mcp_token_id' => $context->token->id,
                    'operation_id' => $operationId,
                ],
                [
                    'operation' => $operation,
                    'status' => McpOperationStatus::Failed,
                    'result' => [
                        'error_code' => $exception instanceof McpException ? $exception->errorCode : 'internal_error',
                    ],
                ],
            );
        } catch (Throwable) {
            // Last resort: the failure record must never prevent the original error from propagating.
        }
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return in_array($exception->errorInfo[0] ?? 0, [23000, 19], true);
    }
}
