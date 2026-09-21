<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Support\Collection;

final class ListProjects
{
    /**
     * @param  array{search?: string|null, status?: string|null, limit?: int|null}  $input
     * @return array{projects: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function handle(array $input): array
    {
        $limit = max(1, min($input['limit'] ?? 20, 100));

        $query = Project::query();

        if (isset($input['search']) && $input['search'] !== '') {
            $query->where(function ($query) use ($input): void {
                $query->where('name', 'like', '%'.$input['search'].'%')
                    ->orWhere('slug', 'like', '%'.$input['search'].'%');
            });
        }

        if (isset($input['status']) && $input['status'] !== '') {
            $query->where('status', $input['status']);
        }

        /** @var Collection<int, Project> $projects */
        $projects = $query
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $summaries = [];

        foreach ($projects as $project) {
            $summaries[] = $this->summarize($project);
        }

        return [
            'projects' => $summaries,
            'meta' => [
                'total' => $projects->count(),
                'limit' => $limit,
                'available_statuses' => ProjectStatus::values(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'status' => $project->status->value,
        ];
    }
}
