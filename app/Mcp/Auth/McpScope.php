<?php

declare(strict_types=1);

namespace App\Mcp\Auth;

enum McpScope: string
{
    case ProjectRead = 'project:read';

    case TaskRead = 'task:read';

    case TaskDraftWrite = 'task:draft:write';

    case NoteRead = 'note:read';

    case NoteWrite = 'note:write';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
