# Laravel MCP Laboratory

A remote [Model Context Protocol](https://modelcontextprotocol.io) server built on Laravel 13 with a human-in-the-loop workflow: MCP clients can read and propose, but **write operations produce drafts that a human must approve** before anything is published.

## Features

- **Remote MCP HTTP endpoint** — JSON-RPC 2.0 at `POST /api/mcp` with bearer-token authentication (`initialize`, `ping`, `tools/list`, `tools/call`).
- **Scoped tokens** — tokens created from the web UI carry explicit scopes (`project:read`, `task:read`, `task:draft:write`, `note:read`, `note:write`).
- **Draft-only writes** — `tasks:create-draft` and `tasks:update-draft` produce `TaskDraft` records. A human approves or rejects them in the web UI; `tasks:update-draft` supports optimistic concurrency via `expected_updated_at`.
- **Idempotency** — write tools accept `operation_id`; replays return the stored result and are never executed twice.
- **Audit trail** — every authenticated tool call (success or failure) is recorded with token, scope, input hash, and duration.
- **Security hardening** — token hashing (SHA-256, plaintext shown once), expiration & revocation, per-token rate limiting, Origin validation, and sanitised error payloads.

## Requirements

- PHP 8.3+
- Composer 2
- Node.js 22
- MySQL (or any supported Laravel database)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB credentials in .env, then:
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

### Quality gates

```bash
vendor/bin/pint --test      # code style
vendor/bin/phpstan analyse  # static analysis (level 7)
php artisan test            # test suite
npm run types:check        # vue-tsc
npm run build
```

## Using the MCP server

### 1. Create a token

Sign in, open **MCP tokens** from the sidebar, and create a token with the scopes you need. Copy the raw token immediately — it is only shown once.

### 2. Call the endpoint

```bash
curl -s http://localhost:8000/api/mcp \
  -H "Authorization: Bearer mcp_..." \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-05","capabilities":{},"clientInfo":{"name":"curl","version":"0"}}}'
```

### 3. Connect OpenCode

Add the remote server to `opencode.json`:

```json
{
    "mcp": {
        "laravel-mcp-lab": {
            "type": "remote",
            "url": "http://localhost:8000/api/mcp",
            "headers": {
                "Authorization": "Bearer {env:LARAVEL_MCP_TOKEN}"
            }
        }
    }
}
```

Export the token (never commit it):

```bash
export LARAVEL_MCP_TOKEN="mcp_..."
```

## Tools

| Tool                 | Scopes             | Notes                                                                  |
| -------------------- | ------------------ | ---------------------------------------------------------------------- |
| `projects:list`      | `project:read`     | Optional `status` / `search` / `limit`                                 |
| `projects:get`       | `project:read`     |                                                                        |
| `tasks:list`         | `task:read`        | Optional `project_id`, `status`, `priority`                            |
| `tasks:get`          | `task:read`        | Notes included via `include_notes`                                     |
| `tasks:create-draft` | `task:draft:write` | Requires `operation_id`; creates a draft                               |
| `tasks:update-draft` | `task:draft:write` | Requires `operation_id`, `expected_updated_at`; optimistic concurrency |
| `notes:list`         | `note:read`        |                                                                        |
| `notes:create`       | `note:write`       | Requires `operation_id`; idempotent                                    |

## Human approval flow

MCP cannot publish directly. `tasks:create-draft` / `tasks:update-draft` create drafts; in **Task drafts** a human clicks **Approve** (publishes a `Task`) or **Reject** (optionally with a reason) in the web UI.

## Configuration

See `config/mcp.php` and the `MCP_*` variables in `.env.example`:

- `MCP_ENABLED` — serve the endpoint (`404` when disabled)
- `MCP_ALLOWED_ORIGINS` — comma-separated browser origins allowed
- `MCP_RATE_LIMIT`, `MCP_RATE_LIMIT_DECAY_MINUTES` — per-token rate limit
- `MCP_TOKEN_PREFIX` — token prefix (e.g. `mcp_`)
- `MCP_SERVER_NAME`, `MCP_SERVER_VERSION` — identity advertised on `initialize`
