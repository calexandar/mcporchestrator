<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Notes;

use App\Application\Notes\CreateNote;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpInput;
use App\Mcp\Tools\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateNoteTool extends Tool
{
    protected string $name = 'notes:create';

    protected string $description = 'Create a note on a project. Notes are written immediately and do not require human approval.';

    public function __construct(private readonly CreateNote $service) {}

    public function requiredScope(): McpScope
    {
        return McpScope::NoteWrite;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation_id' => $schema->string()->max(100)->required()->description('Client-generated idempotency key (UUID).'),
            'project_id' => $schema->integer()->required()->description('The project to attach the note to.'),
            'title' => $schema->string()->max(120)->required()->description('Note title.'),
            'body' => $schema->string()->max(10000)->required()->description('Note body.'),
        ];
    }

    protected function run(Request $request): ResponseFactory
    {
        $input = McpInput::make($request->all());

        $operationId = $input->string('operation_id', required: true, max: 100);
        $projectId = $input->int('project_id', required: true);
        $title = $input->string('title', required: true, max: 120);
        $body = $input->string('body', required: true, max: 10000);

        $input->result();

        return Response::structured($this->service->handle($this->context(), [
            'operation_id' => (string) $operationId,
            'project_id' => (int) $projectId,
            'title' => (string) $title,
            'body' => (string) $body,
        ]));
    }
}
