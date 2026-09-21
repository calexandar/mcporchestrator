<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\McpAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class McpAuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = McpAuditLog::query()
            ->with('token:id,name')
            ->when($request->query('success'), fn ($query, $success) => $query->where('success', $success === '1'))
            ->when($request->query('tool'), fn ($query, $tool) => $query->where('tool_name', $tool))
            ->latest('created_at')
            ->limit(200)
            ->get();

        return Inertia::render('mcp/AuditLog', [
            'entries' => $entries,
        ]);
    }
}
