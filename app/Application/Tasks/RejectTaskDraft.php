<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Enums\TaskDraftStatus;
use App\Models\TaskDraft;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RejectTaskDraft
{
    /**
     * Reject a draft so it can never be published.
     */
    public function handle(TaskDraft $draft, User $rejecter, string $reason): void
    {
        DB::transaction(function () use ($draft, $rejecter, $reason): void {
            /** @var TaskDraft $locked */
            $locked = TaskDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($locked->status !== TaskDraftStatus::Draft) {
                throw new DomainException('Only drafts in the "draft" state can be rejected.');
            }

            $locked->update([
                'status' => TaskDraftStatus::Rejected,
                'rejection_reason' => $reason,
                'approved_by' => $rejecter->id,
            ]);
        });
    }
}
