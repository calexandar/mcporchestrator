<?php

declare(strict_types=1);

namespace App\Mcp\Exceptions;

final class StaleResourceConflict extends McpException
{
    public function __construct(string $message = 'The resource changed since it was retrieved. Reload it and try again.')
    {
        parent::__construct(
            errorCode: 'stale_resource',
            message: $message,
            httpStatus: 409,
            jsonRpcCode: -32000,
        );
    }
}
