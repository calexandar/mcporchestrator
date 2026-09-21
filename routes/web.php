<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\McpAuditLogController;
use App\Http\Controllers\McpTokenController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDraftController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('projects', ProjectController::class)->only(['index', 'show']);
    Route::resource('tasks', TaskController::class)->only(['index', 'show']);

    Route::resource('task-drafts', TaskDraftController::class)->only(['index', 'show']);
    Route::patch('task-drafts/{taskDraft}/approve', [TaskDraftController::class, 'approve'])->name('task-drafts.approve');
    Route::patch('task-drafts/{taskDraft}/reject', [TaskDraftController::class, 'reject'])->name('task-drafts.reject');

    Route::get('mcp/tokens', [McpTokenController::class, 'index'])->name('mcp.tokens');
    Route::post('mcp/tokens', [McpTokenController::class, 'store'])->name('mcp.tokens.store');
    Route::patch('mcp/tokens/{mcpToken}/revoke', [McpTokenController::class, 'revoke'])->name('mcp.tokens.revoke');

    Route::get('mcp/audit-log', [McpAuditLogController::class, 'index'])->name('mcp.audit-log');
});

require __DIR__.'/settings.php';
