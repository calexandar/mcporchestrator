<?php

declare(strict_types=1);

namespace App\Mcp\Exceptions;

final class InvalidMcpToken extends McpException
{
    public function __construct(string $message = 'Invalid or missing MCP token.')
    {
        parent::__construct(
            errorCode: 'invalid_mcp_token',
            message: $message,
            httpStatus: 401,
            jsonRpcCode: -32003,
        );
    }
}
