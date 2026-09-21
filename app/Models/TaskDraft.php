<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskDraftStatus;
use App\Enums\TaskPriority;
use Database\Factories\TaskDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $task_id
 * @property string $title
 * @property string|null $description
 * @property TaskDraftStatus $status
 * @property TaskPriority $priority
 * @property string $operation_id
 * @property int|null $created_by_mcp_token_id
 * @property int|null $created_by_user_id
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'project_id',
    'task_id',
    'title',
    'description',
    'status',
    'priority',
    'operation_id',
    'created_by_mcp_token_id',
    'created_by_user_id',
    'approved_at',
    'approved_by',
    'rejection_reason',
])]
class TaskDraft extends Model
{
    /** @use HasFactory<TaskDraftFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskDraftStatus::class,
            'priority' => TaskPriority::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<McpToken, $this>
     */
    public function creatorToken(): BelongsTo
    {
        return $this->belongsTo(McpToken::class, 'created_by_mcp_token_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
