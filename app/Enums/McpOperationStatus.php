<?php

declare(strict_types=1);

namespace App\Enums;

enum McpOperationStatus: string
{
    case Processing = 'processing';

    case Completed = 'completed';

    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
