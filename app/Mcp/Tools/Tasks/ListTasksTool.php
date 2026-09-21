<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Tasks;

use App\Application\Tasks\ListTasks;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ListTasksTool extends Tool
{
    protected string $name = 'tasks:list';

    protected string $description = 'List tasks, optionally filtered by project, status, priority, and a result limit.';

    public function __construct(private readonly ListTasks $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::TaskRead;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Filter by project ID.'),
            'status' => $schema->string()->enum(TaskStatus::class)->description('Filter by task status.'),
            'priority' => $schema->string()->enum(TaskPriority::class)->description('Filter by task priority.'),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maximum results (default 20).'),
        ];
    }

    protected function run(Request $request): Response|ResponseFactory
    {
        $input = McpInput::make($request->all());

        $projectId = $input->int('project_id');
        $status = $input->oneOf('status', TaskStatus::values());
        $priority = $input->oneOf('priority', TaskPriority::values());
        $limit = $input->int('limit');

        $input->result();

        return Response::structured($this->service->handle([
            'project_id' => $projectId,
            'status' => $status,
            'priority' => $priority,
            'limit' => $limit,
        ]));
    }
}
