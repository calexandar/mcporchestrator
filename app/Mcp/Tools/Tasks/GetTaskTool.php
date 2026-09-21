<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Tasks;

use App\Application\Tasks\GetTask;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class GetTaskTool extends Tool
{
    protected string $name = 'tasks:get';

    protected string $description = 'Get a single task, optionally including its notes.';

    public function __construct(private readonly GetTask $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::TaskRead;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->required()->description('The task ID.'),
            'include_notes' => $schema->boolean()->description('Whether to include the task notes.'),
        ];
    }

    protected function run(Request $request): Response|ResponseFactory
    {
        $input = McpInput::make($request->all());

        $taskId = $input->int('task_id', required: true);
        $includeNotes = $input->bool('include_notes');

        $input->result();

        return Response::structured($this->service->handle((int) $taskId, $includeNotes));
    }
}
