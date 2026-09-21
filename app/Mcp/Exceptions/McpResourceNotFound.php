<?php

declare(strict_types=1);

namespace App\Mcp\Exceptions;

final class McpResourceNotFound extends McpException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct(
            errorCode: 'resource_not_found',
            message: $message,
            httpStatus: 404,
            jsonRpcCode: -32001,
        );
    }
}
