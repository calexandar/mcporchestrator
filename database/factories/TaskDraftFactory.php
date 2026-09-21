<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskDraftStatus;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\TaskDraft;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaskDraft>
 */
class TaskDraftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TaskDraftStatus::Draft,
            'priority' => TaskPriority::Medium,
            'operation_id' => (string) Str::uuid(),
            'created_by_mcp_token_id' => null,
            'created_by_user_id' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
        ];
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskDraftStatus::Rejected,
            'rejection_reason' => 'Out of scope for the current milestone.',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskDraftStatus::Published,
        ]);
    }
}
