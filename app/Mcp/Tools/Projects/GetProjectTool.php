<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Projects;

use App\Application\Projects\GetProject;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class GetProjectTool extends Tool
{
    protected string $name = 'projects:get';

    protected string $description = 'Get a single project with its task counts by status and total notes.';

    public function __construct(private readonly GetProject $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::ProjectRead;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->required()->description('The project ID.'),
        ];
    }

    protected function run(Request $request): ResponseFactory
    {
        $input = McpInput::make($request->all());

        $projectId = $input->int('project_id', required: true);

        $input->result();

        return Response::structured($this->service->handle((int) $projectId));
    }
}
