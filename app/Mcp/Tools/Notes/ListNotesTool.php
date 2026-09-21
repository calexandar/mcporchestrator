<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Notes;

use App\Application\Notes\ListNotes;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ListNotesTool extends Tool
{
    protected string $name = 'notes:list';

    protected string $description = 'List notes, optionally filtered by project and a result limit.';

    public function __construct(private readonly ListNotes $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::NoteRead;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Filter by project ID.'),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maximum results (default 20).'),
        ];
    }

    protected function run(Request $request): ResponseFactory
    {
        $input = McpInput::make($request->all());

        $projectId = $input->int('project_id');
        $limit = $input->int('limit');

        $input->result();

        return Response::structured($this->service->handle([
            'project_id' => $projectId,
            'limit' => $limit,
        ]));
    }
}
