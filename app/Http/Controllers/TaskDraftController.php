<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Tasks\ApproveTaskDraft;
use App\Application\Tasks\RejectTaskDraft;
use App\Http\Requests\Tasks\RejectTaskDraftRequest;
use App\Models\TaskDraft;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TaskDraftController extends Controller
{
    public function __construct(
        private readonly ApproveTaskDraft $approveDraft,
        private readonly RejectTaskDraft $rejectDraft,
    ) {}

    public function index(Request $request): Response
    {
        $drafts = TaskDraft::query()
            ->with(['project:id,name', 'creatorToken:id,name'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('updated_at')
            ->get();

        return Inertia::render('task-drafts/Index', [
            'drafts' => $drafts,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function show(TaskDraft $taskDraft): Response
    {
        return Inertia::render('task-drafts/Show', [
            'draft' => $taskDraft->load([
                'project:id,name',
                'creatorToken:id,name',
                'creatorUser:id,name',
                'approver:id,name',
            ]),
        ]);
    }

    public function approve(Request $request, TaskDraft $taskDraft): RedirectResponse
    {
        try {
            $task = $this->approveDraft->handle($taskDraft, $request->user());
        } catch (DomainException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft approved and published.']);

        return redirect(route('tasks.show', $task));
    }

    public function reject(RejectTaskDraftRequest $request, TaskDraft $taskDraft): RedirectResponse
    {
        try {
            $this->rejectDraft->handle($taskDraft, $request->user(), $request->validated('reason'));
        } catch (DomainException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft rejected.']);

        return back();
    }
}
