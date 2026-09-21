<?php

declare(strict_types=1);

namespace App\Mcp\Exceptions;

final class McpOperationConflict extends McpException
{
    public function __construct(string $message = 'The operation is currently processing.')
    {
        parent::__construct(
            errorCode: 'operation_conflict',
            message: $message,
            httpStatus: 409,
            jsonRpcCode: -32000,
        );
    }
}
