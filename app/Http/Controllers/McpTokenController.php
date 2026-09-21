<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Mcp\StoreMcpTokenRequest;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpTokenService;
use App\Models\McpToken;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class McpTokenController extends Controller
{
    public function __construct(private readonly McpTokenService $tokens) {}

    public function index(): Response
    {
        return Inertia::render('mcp/Tokens', [
            'tokens' => McpToken::query()
                ->with('creator:id,name')
                ->withCount('operations')
                ->withCount('auditLogs')
                ->latest()
                ->get(),
            'scopes' => McpScope::values(),
            'newToken' => session()->get('mcp_new_token'),
            'newTokenPrefix' => session()->get('mcp_new_token_prefix'),
        ]);
    }

    public function store(StoreMcpTokenRequest $request): RedirectResponse
    {
        $days = $request->validated('days');

        $result = $this->tokens->generate(
            name: $request->validated('name'),
            scopes: $request->validated('scopes'),
            expiresInDays: $days === null ? null : (int) $days,
            createdBy: (int) $request->user()->id,
        );

        session()->flash('mcp_new_token', $result['raw']);
        session()->flash('mcp_new_token_prefix', $result['token']->token_prefix);

        return back();
    }

    public function revoke(McpToken $mcpToken): RedirectResponse
    {
        $this->tokens->revoke($mcpToken);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token revoked.']);

        return back();
    }
}
