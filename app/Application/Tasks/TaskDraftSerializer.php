<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Models\TaskDraft;

final class TaskDraftSerializer
{
    /**
     * @return array<string, mixed>
     */
    public static function forMcp(TaskDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'project_id' => $draft->project_id,
            'task_id' => $draft->task_id,
            'title' => $draft->title,
            'description' => $draft->description,
            'status' => $draft->status->value,
            'priority' => $draft->priority->value,
            'source' => $draft->created_by_mcp_token_id !== null ? 'mcp' : 'user',
            'created_at' => $draft->created_at?->toIso8601String(),
            'updated_at' => $draft->updated_at->toIso8601String(),
        ];
    }
}
