<?php

declare(strict_types=1);

namespace App\Application\Notes;

use App\Enums\ProjectStatus;
use App\Mcp\Auth\McpContext;
use App\Mcp\Exceptions\McpResourceNotFound;
use App\Mcp\Exceptions\McpValidationFailed;
use App\Mcp\Operations\McpOperationService;
use App\Models\Note;
use App\Models\Project;

final class CreateNote
{
    public function __construct(private readonly McpOperationService $operations) {}

    /**
     * @param  array{operation_id: string, project_id: int, title: string, body: string}  $input
     * @return array<string, mixed>
     */
    public function handle(McpContext $context, array $input): array
    {
        return $this->operations->execute(
            context: $context,
            operationId: $input['operation_id'],
            operation: 'create-note',
            callback: fn (): array => $this->create($context, $input),
        );
    }

    /**
     * @param  array{operation_id: string, project_id: int, title: string, body: string}  $input
     * @return array{note: array<string, mixed>}
     */
    private function create(McpContext $context, array $input): array
    {
        $project = Project::query()->find($input['project_id']);

        if ($project === null) {
            throw new McpResourceNotFound("Project [{$input['project_id']}] does not exist.");
        }

        if ($project->status === ProjectStatus::Archived) {
            throw new McpValidationFailed([
                'project_id' => ['You cannot add notes to an archived project.'],
            ]);
        }

        $note = Note::query()->create([
            'project_id' => $project->id,
            'title' => $input['title'],
            'body' => $input['body'],
            'created_by' => null,
        ]);

        return [
            'note' => [
                'id' => $note->id,
                'project_id' => $note->project_id,
                'title' => $note->title,
                'body' => $note->body,
                'created_at' => $note->created_at?->toIso8601String(),
            ],
        ];
    }
}
