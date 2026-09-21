<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Mcp\Exceptions\McpValidationFailed;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

final class ListTasks
{
    /**
     * @param  array{project_id?: int|null, status?: string|null, priority?: string|null, limit?: int|null}  $input
     * @return array{tasks: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function handle(array $input): array
    {
        $limit = max(1, min($input['limit'] ?? 20, 100));

        $query = Task::query();

        if (($input['project_id'] ?? null) !== null) {
            if (Project::query()->whereKey($input['project_id'])->doesntExist()) {
                throw new McpValidationFailed([
                    'project_id' => ['The selected project does not exist.'],
                ]);
            }

            $query->where('project_id', $input['project_id']);
        }

        if (isset($input['status']) && $input['status'] !== '') {
            $query->where('status', $input['status']);
        }

        if (isset($input['priority']) && $input['priority'] !== '') {
            $query->where('priority', $input['priority']);
        }

        /** @var Collection<int, Task> $tasks */
        $tasks = $query
            ->with('project:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $summaries = [];

        foreach ($tasks as $task) {
            $summaries[] = $this->summarize($task);
        }

        return [
            'tasks' => $summaries,
            'meta' => [
                'total' => $tasks->count(),
                'limit' => $limit,
                'available_statuses' => TaskStatus::values(),
                'available_priorities' => TaskPriority::values(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Task $task): array
    {
        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
        ];
    }
}
