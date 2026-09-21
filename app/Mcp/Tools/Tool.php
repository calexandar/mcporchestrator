<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Audit\McpAuditor;
use App\Mcp\Auth\McpContext;
use App\Mcp\Auth\McpScope;
use App\Mcp\Exceptions\McpException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool as LaravelTool;

abstract class Tool extends LaravelTool
{
    /**
     * The single scope required to invoke this tool.
     */
    abstract public function requiredScope(): McpScope;

    /**
     * Execute the tool and build the structured result payload.
     */
    abstract protected function run(Request $request): Response|ResponseFactory;

    final public function shouldRegister(): bool
    {
        return $this->context()->hasScope($this->requiredScope());
    }

    final public function handle(Request $request): Response|ResponseFactory
    {
        $context = $this->context();
        $arguments = $request->all();
        $startedAtNanos = hrtime(true);

        $operationId = is_string($arguments['operation_id'] ?? null) ? $arguments['operation_id'] : null;

        try {
            $response = $this->run($request);

            $this->recordAudit($context, $arguments, $startedAtNanos, $operationId, success: true);

            return $response;
        } catch (McpException $exception) {
            $this->recordAudit($context, $arguments, $startedAtNanos, $operationId, success: false, errorCode: $exception->errorCode);

            return Response::make(Response::error($exception->getMessage()))
                ->withStructuredContent([
                    'error' => [
                        'code' => $exception->errorCode,
                        'details' => $exception->details,
                    ],
                ]);
        }
    }

    protected function context(): McpContext
    {
        return app(McpContext::class);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function recordAudit(
        McpContext $context,
        array $arguments,
        int $startedAtNanos,
        ?string $operationId,
        bool $success,
        ?string $errorCode = null,
    ): void {
        app(McpAuditor::class)->recordToolCall(
            context: $context,
            toolName: $this->name(),
            requiredScope: $this->requiredScope(),
            arguments: $arguments,
            startedAtNanos: $startedAtNanos,
            success: $success,
            errorCode: $errorCode,
            operationId: $operationId,
        );
    }
}
