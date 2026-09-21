<?php

declare(strict_types=1);

namespace App\Application\Tasks;

use App\Enums\ProjectStatus;
use App\Enums\TaskDraftStatus;
use App\Enums\TaskPriority;
use App\Mcp\Auth\McpContext;
use App\Mcp\Exceptions\McpResourceNotFound;
use App\Mcp\Exceptions\McpValidationFailed;
use App\Mcp\Operations\McpOperationService;
use App\Models\Project;
use App\Models\TaskDraft;

final class CreateTaskDraft
{
    public function __construct(private readonly McpOperationService $operations) {}

    /**
     * @param  array{operation_id: string, project_id: int, title: string, description?: string|null, priority: string}  $input
     * @return array<string, mixed>
     */
    public function handle(McpContext $context, array $input): array
    {
        return $this->operations->execute(
            context: $context,
            operationId: $input['operation_id'],
            operation: 'create-task-draft',
            callback: fn (): array => $this->create($context, $input),
        );
    }

    /**
     * @param  array{operation_id: string, project_id: int, title: string, description?: string|null, priority: string}  $input
     * @return array{draft: array<string, mixed>}
     */
    private function create(McpContext $context, array $input): array
    {
        $project = Project::query()->find($input['project_id']);

        if ($project === null) {
            throw new McpResourceNotFound("Project [{$input['project_id']}] does not exist.");
        }

        if ($project->status === ProjectStatus::Archived) {
            throw new McpValidationFailed([
                'project_id' => ['You cannot create drafts for an archived project.'],
            ]);
        }

        $draft = TaskDraft::query()->create([
            'project_id' => $project->id,
            'title' => $input['title'],
            'description' => $input['description'] ?? null,
            'status' => TaskDraftStatus::Draft,
            'priority' => TaskPriority::from($input['priority']),
            'operation_id' => $input['operation_id'],
            'created_by_mcp_token_id' => $context->token->id,
        ]);

        return ['draft' => TaskDraftSerializer::forMcp($draft)];
    }
}
