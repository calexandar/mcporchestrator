<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskDraftStatus;
use App\Enums\TaskStatus;
use App\Models\McpAuditLog;
use App\Models\McpToken;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDraft;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'projects' => Project::count(),
            'active_projects' => Project::where('status', ProjectStatus::Active)->count(),
            'tasks' => Task::count(),
            'open_tasks' => Task::whereIn('status', [
                TaskStatus::Todo,
                TaskStatus::InProgress,
                TaskStatus::Review,
            ])->count(),
            'pending_drafts' => TaskDraft::where('status', TaskDraftStatus::Draft)->count(),
            'notes' => Note::count(),
            'mcp_tokens' => McpToken::count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentDrafts' => TaskDraft::query()
                ->with('project:id,name')
                ->where('status', TaskDraftStatus::Draft)
                ->latest('updated_at')
                ->take(5)
                ->get(),
            'recentTokens' => McpToken::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'token_prefix', 'expires_at', 'revoked_at', 'last_used_at']),
            'recentAudit' => McpAuditLog::query()
                ->with('token:id,name')
                ->latest('created_at')
                ->take(8)
                ->get(),
        ]);
    }
}
