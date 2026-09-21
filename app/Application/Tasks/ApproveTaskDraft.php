<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Enums\TaskDraftStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskDraft;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ApproveTaskDraft
{
    /**
     * Approve a draft and publish it as a real task. Only callable by a human.
     */
    public function handle(TaskDraft $draft, User $approver): Task
    {
        return DB::transaction(function () use ($draft, $approver): Task {
            /** @var TaskDraft $locked */
            $locked = TaskDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($locked->status !== TaskDraftStatus::Draft) {
                throw new DomainException('Only drafts in the "draft" state can be approved.');
            }

            if ($locked->task_id !== null) {
                /** @var Task $task */
                $task = Task::query()->lockForUpdate()->findOrFail($locked->task_id);

                $task->update([
                    'title' => $locked->title,
                    'description' => $locked->description,
                    'priority' => $locked->priority,
                    'approved_at' => now(),
                    'approved_by' => $approver->id,
                ]);
            } else {
                $task = Task::query()->create([
                    'project_id' => $locked->project_id,
                    'title' => $locked->title,
                    'description' => $locked->description,
                    'status' => TaskStatus::Todo,
                    'priority' => $locked->priority,
                    'created_by' => $approver->id,
                    'approved_at' => now(),
                    'approved_by' => $approver->id,
                ]);
            }

            $locked->update([
                'task_id' => $task->id,
                'status' => TaskDraftStatus::Published,
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]);

            return $task;
        });
    }
}
