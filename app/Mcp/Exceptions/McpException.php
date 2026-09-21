<?php

declare(strict_types=1);

namespace App\Mcp\Exceptions;

use RuntimeException;
use Throwable;

class McpException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus,
        public readonly int $jsonRpcCode = -32603,
        public readonly ?array $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
