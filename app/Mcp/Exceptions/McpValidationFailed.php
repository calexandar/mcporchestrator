<?php

declare(strict_types=1);

namespace App\Mcp\Exceptions;

final class McpValidationFailed extends McpException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct(
            errorCode: 'validation_failed',
            message: 'The provided parameters are invalid.',
            httpStatus: 422,
            jsonRpcCode: -32602,
            details: ['errors' => $errors],
        );
    }
}
