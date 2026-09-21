<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskDraftStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::query()
            ->withCount('tasks', 'notes')
            ->orderBy('name')
            ->get();

        return Inertia::render('projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project): Response
    {
        /** @var Collection<int, Task> $tasks */
        $tasks = $project->tasks()
            ->latest()
            ->get();

        return Inertia::render('projects/Show', [
            'project' => $project->loadCount('tasks', 'notes'),
            'tasks' => $tasks,
            'notes' => $project->notes()->latest()->get(),
            'drafts' => $project->taskDrafts()
                ->where('status', '!=', TaskDraftStatus::Draft->value)
                ->latest()->get(['id', 'title', 'status', 'updated_at']),
        ]);
    }
}
