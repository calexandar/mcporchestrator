<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP Endpoint Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled, POST /api/mcp returns 404. Useful for environments
    | where the MCP server should not be exposed at all.
    |
    */

    'enabled' => env('MCP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Comma separated list of origins permitted to call the MCP endpoint from
    | a browser. Native / server MCP clients do not send an Origin header and
    | are therefore always allowed.
    |
    | Do not rely on this as a replacement for authentication.
    |
    */

    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) env('MCP_ALLOWED_ORIGINS', '')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Max requests allowed per token per decay window. Set to 0 to disable.
    |
    */

    'rate_limit' => (int) env('MCP_RATE_LIMIT', 60),

    'rate_limit_decay_minutes' => (int) env('MCP_RATE_LIMIT_DECAY_MINUTES', 1),

    /*
    |--------------------------------------------------------------------------
    | Token Format
    |--------------------------------------------------------------------------
    |
    | Prefix used when generating new MCP tokens (e.g. "mcp_"). The raw token
    | is never stored; only a SHA-256 hash is persisted.
    |
    */

    'token_prefix' => env('MCP_TOKEN_PREFIX', 'mcp_'),

    /*
    |--------------------------------------------------------------------------
    | Server Identity
    |--------------------------------------------------------------------------
    |
    | Name and version advertised to MCP clients during the initialize
    | handshake.
    |
    */

    'server_name' => env('MCP_SERVER_NAME', 'laravel-mcp-lab'),

    'server_version' => env('MCP_SERVER_VERSION', '1.0.0'),

];
