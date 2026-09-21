<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Tasks;

use App\Application\Tasks\CreateTaskDraft;
use App\Enums\TaskPriority;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateTaskDraftTool extends Tool
{
    protected string $name = 'tasks:create-draft';

    protected string $description = 'Create a task draft for a project. Drafts are not published until a human approves them in the web app.';

    public function __construct(private readonly CreateTaskDraft $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::TaskDraftWrite;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation_id' => $schema->string()->max(100)->required()->description('Client-generated idempotency key (UUID).'),
            'project_id' => $schema->integer()->required()->description('The project to create the draft in.'),
            'title' => $schema->string()->max(255)->required()->description('Task title.'),
            'description' => $schema->string()->max(1000)->description('Task description.'),
            'priority' => $schema->string()->enum(TaskPriority::class)->default('medium')->description('Task priority.'),
        ];
    }

    protected function run(Request $request): Response|ResponseFactory
    {
        $input = McpInput::make($request->all());

        $operationId = $input->string('operation_id', required: true, max: 100);
        $projectId = $input->int('project_id', required: true);
        $title = $input->string('title', required: true, max: 255);
        $description = $input->string('description', max: 1000);
        $priority = $input->oneOf('priority', TaskPriority::values(), default: 'medium');

        $input->result();

        return Response::structured($this->service->handle($this->context(), [
            'operation_id' => (string) $operationId,
            'project_id' => (int) $projectId,
            'title' => (string) $title,
            'description' => $description,
            'priority' => (string) $priority,
        ]));
    }
}
