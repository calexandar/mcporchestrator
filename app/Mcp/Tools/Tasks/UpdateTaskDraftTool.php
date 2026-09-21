<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Tasks;

use App\Application\Tasks\UpdateTaskDraft;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class UpdateTaskDraftTool extends Tool
{
    protected string $name = 'tasks:update-draft';

    protected string $description = 'Update a draft title or description. Requires the draft updated_at returned by a previous read for optimistic concurrency.';

    public function __construct(private readonly UpdateTaskDraft $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::TaskDraftWrite;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation_id' => $schema->string()->max(100)->required()->description('Client-generated idempotency key (UUID).'),
            'draft_id' => $schema->integer()->required()->description('The draft ID.'),
            'expected_updated_at' => $schema->string()->max(100)->required()->format('date-time')->description('The updated_at value from the last read of this draft.'),
            'title' => $schema->string()->max(255)->description('New title.'),
            'description' => $schema->string()->max(1000)->description('New description.'),
        ];
    }

    protected function run(Request $request): ResponseFactory
    {
        $input = McpInput::make($request->all());

        $operationId = $input->string('operation_id', required: true, max: 100);
        $draftId = $input->int('draft_id', required: true);
        $expectedUpdatedAt = $input->string('expected_updated_at', required: true, max: 100);
        $title = $input->string('title', max: 255);
        $description = $input->string('description', max: 1000);

        $input->result();

        return Response::structured($this->service->handle($this->context(), [
            'operation_id' => (string) $operationId,
            'draft_id' => (int) $draftId,
            'expected_updated_at' => (string) $expectedUpdatedAt,
            'title' => $title,
            'description' => $description,
        ]));
    }
}
