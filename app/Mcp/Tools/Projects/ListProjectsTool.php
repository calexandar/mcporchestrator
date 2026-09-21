<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Projects;

use App\Application\Projects\ListProjects;
use App\Enums\ProjectStatus;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ListProjectsTool extends Tool
{
    protected string $name = 'projects:list';

    protected string $description = 'List projects, optionally filtered by search term, status, and a result limit.';

    public function __construct(private readonly ListProjects $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::ProjectRead;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->max(100)->description('Free-text search on project name or slug.'),
            'status' => $schema->string()->enum(ProjectStatus::class)->description('Filter by project status.'),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maximum results (default 20).'),
        ];
    }

    protected function run(Request $request): Response|ResponseFactory
    {
        $input = McpInput::make($request->all());

        $search = $input->string('search', max: 100);
        $status = $input->oneOf('status', ProjectStatus::values());
        $limit = $input->int('limit');

        $input->result();

        return Response::structured($this->service->handle([
            'search' => $search,
            'status' => $status,
            'limit' => $limit,
        ]));
    }
}
