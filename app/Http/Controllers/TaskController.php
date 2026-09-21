<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('tasks/Index', [
            'tasks' => Task::query()
                ->with('project:id,name')
                ->latest()
                ->get(),
        ]);
    }

    public function show(Task $task): Response
    {
        return Inertia::render('tasks/Show', [
            'task' => $task->load(['project:id,name', 'creator:id,name', 'approver:id,name']),
            'notes' => $task->notes()->with('creator:id,name')->latest()->get(),
            'drafts' => $task->drafts()->latest()->get(),
        ]);
    }
}
