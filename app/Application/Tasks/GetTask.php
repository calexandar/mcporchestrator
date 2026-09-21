<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Mcp\Exceptions\McpResourceNotFound;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Support\Collection;

final class GetTask
{
    /**
     * @return array{task: array<string, mixed>}
     */
    public function handle(int $taskId, bool $includeNotes = false): array
    {
        $query = Task::query()->with(['project:id,name', 'creator:id,name', 'approver:id,name']);

        if ($includeNotes) {
            $query->with('notes:id,task_id,title,body,created_by,created_at');
        }

        $task = $query->find($taskId);

        if ($task === null) {
            throw new McpResourceNotFound("Task [{$taskId}] does not exist.");
        }

        $payload = [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'project' => $task->project?->name,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'created_by' => $task->creator?->name,
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'approved_at' => $task->approved_at?->toIso8601String(),
            'approved_by' => $task->approver?->name,
        ];

        if ($includeNotes) {
            /** @var Collection<int, Note> $notes */
            $notes = $task->notes;

            $summaries = [];

            foreach ($notes as $note) {
                $summaries[] = [
                    'id' => $note->id,
                    'title' => $note->title,
                    'body' => $note->body,
                    'created_by' => $note->creator?->name,
                    'created_at' => $note->created_at?->toIso8601String(),
                ];
            }

            $payload['notes'] = $summaries;
        }

        return ['task' => $payload];
    }
}
