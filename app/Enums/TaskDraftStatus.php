<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskDraftStatus: string
{
    case Draft = 'draft';

    case Approved = 'approved';

    case Rejected = 'rejected';

    case Published = 'published';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
