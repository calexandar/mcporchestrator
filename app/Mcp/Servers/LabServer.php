<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\Notes\CreateNoteTool;
use App\Mcp\Tools\Notes\ListNotesTool;
use App\Mcp\Tools\Projects\GetProjectTool;
use App\Mcp\Tools\Projects\ListProjectsTool;
use App\Mcp\Tools\Tasks\CreateTaskDraftTool;
use App\Mcp\Tools\Tasks\GetTaskTool;
use App\Mcp\Tools\Tasks\ListTasksTool;
use App\Mcp\Tools\Tasks\UpdateTaskDraftTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;

final class LabServer extends Server
{
    protected string $instructions = 'This MCP server lets AI agents interact with the mcpOrchestrator application: list projects and tasks, read notes, and write task drafts and notes subject to human approval and idempotency.';

    /**
     * @var array<string, array<string, bool>>
     */
    protected array $capabilities = [
        self::CAPABILITY_TOOLS => [
            'listChanged' => false,
        ],
    ];

    /**
     * @var array<int, class-string>
     */
    protected array $tools = [
        ListProjectsTool::class,
        GetProjectTool::class,
        ListTasksTool::class,
        GetTaskTool::class,
        CreateTaskDraftTool::class,
        UpdateTaskDraftTool::class,
        ListNotesTool::class,
        CreateNoteTool::class,
    ];

    public function __construct(Transport $transport)
    {
        $this->name = (string) config('mcp.server_name', 'laravel-mcp-lab');
        $this->version = (string) config('mcp.server_version', '1.0.0');

        parent::__construct($transport);
    }
}
