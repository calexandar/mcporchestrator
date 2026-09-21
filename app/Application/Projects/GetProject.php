<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Enums\TaskStatus;
use App\Mcp\Exceptions\McpResourceNotFound;
use App\Models\Project;

final class GetProject
{
    /**
     * @return array{project: array<string, mixed>}
     */
    public function handle(int $projectId): array
    {
        $project = Project::query()->with('creator:id,name')->find($projectId);

        if ($project === null) {
            throw new McpResourceNotFound("Project [{$projectId}] does not exist.");
        }

        /** @var array<string, int> $taskCounts */
        $taskCounts = $project->tasks()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $normalizedCounts = [];

        foreach (TaskStatus::cases() as $status) {
            $normalizedCounts[$status->value] = $taskCounts[$status->value] ?? 0;
        }

        $normalizedCounts['total'] = (int) array_sum($normalizedCounts);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'status' => $project->status->value,
                'created_by' => [
                    'id' => $project->creator?->id,
                    'name' => $project->creator?->name,
                ],
                'task_counts' => $normalizedCounts,
                'notes_count' => $project->notes()->count(),
                'created_at' => $project->created_at?->toIso8601String(),
                'updated_at' => $project->updated_at?->toIso8601String(),
            ],
        ];
    }
}
