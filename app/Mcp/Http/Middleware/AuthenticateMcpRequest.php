<?php

declare(strict_types=1);

namespace App\Mcp\Http\Middleware;

use App\Mcp\Auth\McpAuthenticator;
use App\Mcp\Auth\McpContext;
use App\Mcp\Exceptions\InvalidMcpToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

final class AuthenticateMcpRequest
{
    /**
     * Handle an incoming MCP transport request.
     *
     * Authenticates the bearer token, enforces origin and rate-limit policy,
     * and binds the resolved McpContext for the server's tools.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! config('mcp.enabled')) {
            abort(404);
        }

        $origin = $request->headers->get('Origin');

        if (is_string($origin) && $origin !== '') {
            $allowed = (array) config('mcp.allowed_origins', []);

            if ($allowed === [] || ! in_array($origin, $allowed, true)) {
                return $this->errorResponse('origin_not_allowed', "Origin [{$origin}] is not allowed.", 403);
            }
        }

        try {
            $context = app(McpAuthenticator::class)->authenticate($request);
        } catch (InvalidMcpToken $exception) {
            return $this->errorResponse($exception->errorCode, $exception->getMessage(), 401);
        }

        $this->consumeRateLimit((int) $context->token->id);

        app()->instance(McpContext::class, $context);

        return $next($request);
    }

    private function consumeRateLimit(int $tokenId): void
    {
        $limit = (int) config('mcp.rate_limit', 60);
        $decay = (int) config('mcp.rate_limit_decay_minutes', 1);

        if ($limit < 1 || $decay < 1) {
            return;
        }

        $key = "mcp:{$tokenId}";

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            abort(429, 'Too many requests. Please try again later.');
        }

        RateLimiter::hit($key, $decay * 60);
    }

    private function errorResponse(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
