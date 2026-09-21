# Laravel 13 MCP Lab — Implementation Specification

## 1. Project Goal

Build a Laravel 13 application that recreates the important architectural ideas demonstrated by the WhatTheStackConf/WTS MCP implementation for learning and testing purposes.

This is **not** a copy of WTS source code. Implement the same general concepts using Laravel-native architecture:

- Remote MCP HTTP endpoint
- Bearer-token authentication
- Fine-grained MCP scopes
- MCP tool discovery and invocation
- Read-only tools
- Draft-only write operations
- Human approval before publishing
- Idempotent write operations
- Optimistic concurrency protection
- MCP audit logging
- OpenCode remote MCP integration
- MCP Inspector compatibility
- Automated tests
- Clean Laravel service/domain boundaries

The application should be useful as a reusable MCP laboratory for future Laravel projects.

---

# 2. Technical Stack

Use:

- PHP 8.4+
- Laravel 13
- MySQL 8+
- Laravel Sanctum where useful for the normal web application
- Inertia.js
- Vue 3
- Tailwind CSS 4
- Vite
- PHPUnit
- Laravel Pint
- PHPStan / Larastan
- Laravel HTTP client for external integrations
- MCP-compatible PHP implementation or maintained Laravel MCP package where appropriate

Do not introduce unnecessary frameworks.

Keep the MCP layer independent from the normal web UI.

---

# 3. Core Architecture

Use the following architecture:

```text
                         OpenCode
                            |
                            | HTTPS / HTTP
                            v
                     POST /api/mcp
                            |
                            v
                    MCP Authentication
                            |
                            v
                      Scope Checking
                            |
                            v
                       MCP Router
                            |
              +-------------+-------------+
              |             |             |
              v             v             v
          Read Tools    Draft Tools   Utility Tools
              |             |             |
              +-------------+-------------+
                            |
                            v
                     Application Layer
                            |
              +-------------+-------------+
              |                           |
              v                           v
        Domain/Services              Policies
              |                           |
              +-------------+-------------+
                            |
                            v
                         Models
                            |
                            v
                         MySQL
```

MCP tools must not directly contain database-heavy business logic.

Prefer:

```text
MCP Tool
    -> Application Service / Action
        -> Domain rules / Policy
            -> Repository or Eloquent model
```

---

# 4. Example Business Domain

Create a small project-management domain for the MCP laboratory.

Main entities:

```text
User
Project
Task
TaskDraft
Note
McpToken
McpTokenScope
McpOperation
McpAuditLog
```

The MCP API should operate on these entities.

---

# 5. User Experience

The normal Laravel web application should contain:

```text
/dashboard

/projects
/projects/{project}

/tasks
/tasks/{task}

/task-drafts
/task-drafts/{taskDraft}

/mcp/tokens
/mcp/audit-log
```

The web UI is primarily for humans.

MCP is primarily for AI clients.

---

# 6. Database Schema

## 6.1 projects

Create:

```text
projects
```

Fields:

```text
id
name
slug
description nullable
status
created_by
created_at
updated_at
```

Status values:

```text
active
archived
```

Relationships:

```text
Project belongsTo User
Project hasMany Tasks
Project hasMany Notes
```

---

## 6.2 tasks

Create:

```text
tasks
```

Fields:

```text
id
project_id
title
description nullable
status
priority
created_by
approved_at nullable
approved_by nullable
created_at
updated_at
```

Status:

```text
todo
in_progress
review
done
```

Priority:

```text
low
medium
high
```

Relationships:

```text
Task belongsTo Project
Task belongsTo User as creator
Task belongsTo User as approver
Task hasMany Notes
```

---

## 6.3 task_drafts

Create:

```text
task_drafts
```

Fields:

```text
id
project_id
task_id nullable
title
description nullable
status
priority
operation_id
created_by_mcp_token_id nullable
created_by_user_id nullable
approved_at nullable
approved_by nullable
created_at
updated_at
```

Status:

```text
draft
approved
rejected
published
```

A draft may represent:

- a new Task
- a proposed update to an existing Task

Never allow MCP to directly publish a Task.

---

# 7. MCP Token System

Create:

```text
mcp_tokens
```

Fields:

```text
id
name
token_hash
token_prefix
created_by
expires_at nullable
last_used_at nullable
revoked_at nullable
created_at
updated_at
```

Never store the raw MCP token.

Only store a secure hash.

Display the raw token only once when it is created.

Example token:

```text
mcp_live_xxxxxxxxxxxxxxxxx
```

The client must receive the token through an environment variable.

---

# 8. MCP Token Scopes

Create:

```text
mcp_token_scopes
```

Fields:

```text
id
mcp_token_id
scope
created_at
updated_at
```

Supported scopes:

```text
project:read
task:read
task:draft:write
note:read
note:write
```

Keep scope names as constants or backed by a PHP enum.

Example:

```php
enum McpScope: string
{
    case ProjectRead = 'project:read';
    case TaskRead = 'task:read';
    case TaskDraftWrite = 'task:draft:write';
    case NoteRead = 'note:read';
    case NoteWrite = 'note:write';
}
```

---

# 9. MCP Operations / Idempotency

Create:

```text
mcp_operations
```

Fields:

```text
id
mcp_token_id
operation_id
operation
status
result nullable
created_at
updated_at
```

Unique constraint:

```text
mcp_token_id + operation_id
```

Statuses:

```text
processing
completed
failed
```

Every mutating MCP operation must accept an `operation_id`.

Example:

```json
{
    "operation_id": "create-task-01JXYZ..."
}
```

If the same token submits the same operation ID again:

- Do not perform the mutation twice.
- Return the original operation result when available.
- If the operation is still processing, return an appropriate conflict/in-progress response.

This protects against AI/client retries.

---

# 10. MCP Audit Log

Create:

```text
mcp_audit_logs
```

Fields:

```text
id
mcp_token_id nullable
operation_id nullable
tool_name
action
scope nullable
input_hash nullable
success
error_code nullable
metadata nullable
created_at
```

Do not store secrets.

Never store:

- raw bearer tokens
- passwords
- API secrets

Store safe metadata only.

Every MCP tool invocation should generate an audit record.

---

# 11. MCP Authentication

Create an MCP authentication service:

```text
app/Mcp/Auth/McpAuthenticator.php
```

Responsibilities:

1. Read `Authorization: Bearer ...`
2. Validate token format
3. Hash the supplied token
4. Locate matching `McpToken`
5. Check revoked status
6. Check expiration
7. Update `last_used_at`
8. Return an authenticated MCP context

Create:

```text
app/Mcp/Auth/McpContext.php
```

The context should contain:

```text
token
scopes
request id
```

Do not rely on the normal Laravel user session for MCP authentication.

---

# 12. Scope Authorization

Create:

```text
app/Mcp/Auth/McpAuthorizer.php
```

API:

```php
$authorizer->require(McpScope::TaskRead);
```

If the token does not have the scope:

```text
403 Forbidden
```

Return a structured MCP-compatible error.

Never silently execute a tool without its required scope.

---

# 13. MCP Endpoint

Create:

```text
POST /api/mcp
```

This endpoint must:

1. Authenticate the MCP token.
2. Parse the MCP request.
3. Resolve the requested method/tool.
4. Validate input.
5. Check required scope.
6. Execute the tool.
7. Write audit information.
8. Return the MCP response.

Do not create separate REST endpoints for every MCP tool unless they are also useful for the normal application.

MCP should be the AI-facing interface.

---

# 14. MCP Tool Organization

Use:

```text
app/Mcp/Tools/
```

Suggested structure:

```text
app/
└── Mcp/
    ├── Auth/
    │   ├── McpAuthenticator.php
    │   ├── McpAuthorizer.php
    │   ├── McpContext.php
    │   └── McpScope.php
    │
    ├── Server/
    │   ├── McpServer.php
    │   └── McpToolRegistry.php
    │
    ├── Tools/
    │   ├── Projects/
    │   │   ├── ListProjects.php
    │   │   └── GetProject.php
    │   │
    │   ├── Tasks/
    │   │   ├── ListTasks.php
    │   │   ├── GetTask.php
    │   │   ├── CreateTaskDraft.php
    │   │   └── UpdateTaskDraft.php
    │   │
    │   └── Notes/
    │       ├── ListNotes.php
    │       └── CreateNote.php
    │
    └── Exceptions/
        ├── InvalidMcpToken.php
        ├── MissingMcpScope.php
        ├── McpConflict.php
        └── McpOperationConflict.php
```

---

# 15. MCP Tool: list_projects

Tool name:

```text
projects:list
```

Required scope:

```text
project:read
```

Input:

```json
{
    "search": "optional string",
    "status": "optional active|archived",
    "limit": 20
}
```

Return concise project summaries.

Do not return unnecessary database fields.

Example:

```json
{
    "projects": [
        {
            "id": 1,
            "name": "Laravel MCP Lab",
            "slug": "laravel-mcp-lab",
            "status": "active"
        }
    ]
}
```

---

# 16. MCP Tool: get_project

Tool name:

```text
projects:get
```

Scope:

```text
project:read
```

Input:

```json
{
    "project_id": 1
}
```

Return:

- project information
- task counts
- basic metadata

Do not dump every related record automatically.

---

# 17. MCP Tool: list_tasks

Tool name:

```text
tasks:list
```

Scope:

```text
task:read
```

Input:

```json
{
    "project_id": 1,
    "status": "todo",
    "priority": "high",
    "limit": 20
}
```

Return concise task summaries.

---

# 18. MCP Tool: get_task

Tool name:

```text
tasks:get
```

Scope:

```text
task:read
```

Return:

```text
id
project
title
description
status
priority
created_at
updated_at
```

Include notes only when explicitly requested.

---

# 19. MCP Tool: create_task_draft

Tool name:

```text
tasks:create-draft
```

Scope:

```text
task:draft:write
```

Input:

```json
{
    "operation_id": "unique-operation-id",
    "project_id": 1,
    "title": "Add MCP audit log viewer",
    "description": "Create an admin page...",
    "priority": "medium"
}
```

Behavior:

1. Validate project.
2. Validate title.
3. Validate priority.
4. Check idempotency.
5. Create a draft.
6. Write audit log.
7. Return the draft.

Never publish the Task.

Return:

```json
{
    "draft": {
        "id": 12,
        "project_id": 1,
        "title": "Add MCP audit log viewer",
        "status": "draft"
    }
}
```

---

# 20. MCP Tool: update_task_draft

Tool name:

```text
tasks:update-draft
```

Scope:

```text
task:draft:write
```

Input:

```json
{
    "operation_id": "unique-operation-id",
    "draft_id": 12,
    "expected_updated_at": "2026-09-21T09:00:00Z",
    "title": "Updated title",
    "description": "Updated description"
}
```

Behavior:

1. Verify operation ID.
2. Load draft.
3. Verify it is editable.
4. Compare `expected_updated_at`.
5. Reject stale updates.
6. Apply changes.
7. Audit the operation.

If the timestamp does not match:

```text
409 Conflict
```

Return a useful error explaining that the draft changed since it was retrieved.

---

# 21. Human Approval

Create a normal authenticated web action:

```text
POST /task-drafts/{draft}/approve
```

Only a human administrator can approve it.

Flow:

```text
MCP
 |
 | create_task_draft
 v
TaskDraft
 |
 | human review
 v
Approve
 |
 v
Task
```

The MCP client must not have a tool such as:

```text
tasks:publish
```

in the initial implementation.

This intentionally creates a human-in-the-loop boundary.

---

# 22. Rejecting a Draft

Add:

```text
POST /task-drafts/{draft}/reject
```

Store:

```text
rejected
```

and prevent future publishing.

Optionally add:

```text
rejection_reason
```

to the draft.

---

# 23. Concurrency

Use database transactions where necessary.

For updates:

```php
DB::transaction(function () {
    // load draft
    // compare expected_updated_at
    // update draft
});
```

Ensure the comparison and update are atomic.

Do not rely only on an application-level check followed by a separate update.

---

# 24. Business/Application Services

Do not put business logic directly inside MCP tools.

Create:

```text
app/Application/
├── Projects/
│   ├── ListProjects.php
│   └── GetProject.php
│
├── Tasks/
│   ├── ListTasks.php
│   ├── GetTask.php
│   ├── CreateTaskDraft.php
│   ├── UpdateTaskDraft.php
│   ├── ApproveTaskDraft.php
│   └── RejectTaskDraft.php
│
└── Notes/
    ├── ListNotes.php
    └── CreateNote.php
```

MCP tools should be thin adapters.

Example:

```text
MCP Tool
    |
    v
CreateTaskDraft Action
    |
    +-- authorization
    +-- validation
    +-- idempotency
    +-- domain rules
    +-- persistence
    +-- audit
```

The same application service should be usable from the normal web application when appropriate.

---

# 25. Validation

Use Laravel Form Requests or dedicated DTO/input objects.

Do not trust MCP input.

Validate:

- IDs
- enum values
- string lengths
- required fields
- relationships
- pagination limits
- operation IDs
- timestamps

Never accept arbitrary model attributes from an MCP client.

Do not implement:

```php
$model->fill($request->all());
```

for MCP mutations.

Use explicit fields.

---

# 26. Rate Limiting

Create an MCP-specific rate limiter.

Example:

```text
mcp: 60 requests/minute/token
```

Make it configurable.

Use Laravel's rate limiting infrastructure.

Return an appropriate rate-limit error.

---

# 27. Origin Protection

Support an environment variable:

```env
MCP_ALLOWED_ORIGINS=
```

Example:

```env
MCP_ALLOWED_ORIGINS=http://localhost:3000,https://trusted-client.example
```

For browser-origin requests:

1. Read `Origin`.
2. Compare with configured allowed origins.
3. Reject unauthorized origins.

Do not use Origin checking as a replacement for authentication.

Native/server MCP clients may not send an Origin header.

---

# 28. Environment Variables

Create:

```env
MCP_ENABLED=true

MCP_ALLOWED_ORIGINS=http://localhost:3000

MCP_RATE_LIMIT=60

MCP_TOKEN_PREFIX=mcp_
```

Do not place MCP tokens in `.env` committed to Git.

Only use environment variables for local development/testing secrets.

Add `.env.example` entries.

---

# 29. OpenCode Configuration

Document:

```json
{
    "$schema": "https://opencode.ai/config.json",
    "mcp": {
        "laravel-mcp-lab": {
            "type": "remote",
            "url": "http://localhost:8000/api/mcp",
            "enabled": true,
            "headers": {
                "Authorization": "Bearer {env:LARAVEL_MCP_TOKEN}"
            }
        }
    }
}
```

The token should be supplied through:

```bash
export LARAVEL_MCP_TOKEN="mcp_..."
```

Never commit the real token.

---

# 30. MCP Inspector

Document how to test the endpoint with MCP Inspector.

The exact command depends on the MCP Inspector version installed.

The important test target is:

```text
http://localhost:8000/api/mcp
```

with:

```text
Authorization: Bearer <token>
```

Verify:

1. Server connection.
2. Tool discovery.
3. Tool schemas.
4. Read tool execution.
5. Scope failures.
6. Draft creation.
7. Idempotent retry.
8. Concurrency conflict.

---

# 31. Tool Discovery

The MCP server must expose tool metadata.

Each tool should define:

```text
name
description
input schema
required scope
```

Example:

```json
{
    "name": "tasks:create-draft",
    "description": "Create a task draft for human review.",
    "inputSchema": {
        "type": "object",
        "required": ["operation_id", "project_id", "title"]
    }
}
```

Descriptions must clearly communicate limitations.

For example:

> Creates a task draft. Does not publish a task.

This helps an AI client understand the safe boundary.

---

# 32. Error Handling

Create consistent error types.

Examples:

```text
401 invalid_mcp_token
403 missing_scope
404 resource_not_found
409 operation_conflict
409 stale_resource
422 validation_failed
429 rate_limited
500 internal_error
```

Never expose:

- stack traces
- SQL queries
- secrets
- token hashes
- internal credentials

in MCP responses.

Log technical details server-side.

---

# 33. Testing Strategy

Use PHPUnit.

Create:

```text
tests/
├── Feature/
│   └── Mcp/
│       ├── AuthenticationTest.php
│       ├── ScopeAuthorizationTest.php
│       ├── ToolDiscoveryTest.php
│       ├── ProjectsTest.php
│       ├── TasksTest.php
│       ├── DraftsTest.php
│       ├── IdempotencyTest.php
│       ├── ConcurrencyTest.php
│       ├── AuditLogTest.php
│       └── RateLimitTest.php
│
└── Unit/
    └── Mcp/
        ├── McpAuthenticatorTest.php
        ├── McpAuthorizerTest.php
        └── McpOperationTest.php
```

---

# 34. Required Tests

## Authentication

Test:

- missing Authorization header
- malformed token
- invalid token
- revoked token
- expired token
- valid token

---

## Scope

Test:

```text
project:read
task:read
task:draft:write
note:read
note:write
```

Verify that each tool rejects tokens without the required scope.

---

## Tool discovery

Verify:

```text
tools/list
```

returns the expected tools.

Do not expose tools that the current token is not authorized to use if the MCP implementation supports scope-aware tool discovery.

---

## Read tools

Test:

```text
projects:list
projects:get
tasks:list
tasks:get
```

Verify:

- valid data
- invalid IDs
- filtering
- limits

---

## Draft creation

Test:

```text
tasks:create-draft
```

Verify:

- draft is created
- task is NOT published
- audit entry exists
- operation entry exists

---

## Idempotency

Call:

```text
tasks:create-draft
operation_id=abc
```

twice.

Expected:

```text
1 draft
1 logical operation
```

not two drafts.

---

## Concurrency

Create a draft.

Change it.

Attempt an update with the old:

```text
expected_updated_at
```

Expected:

```text
409 stale_resource
```

---

## Human approval

Verify:

```text
draft
  -> approve
  -> task
```

and:

```text
draft
  -> reject
```

cannot later be published.

---

# 35. Seed Data

Create realistic demo data.

Seed:

```text
3 users
3 projects
10 tasks
5 notes
```

Create one MCP token through a seeder only for local development if necessary.

Do not create a default production token.

For local testing, document how to generate a token through the admin UI or Artisan command.

---

# 36. Artisan Command

Create:

```bash
php artisan mcp:token
```

Interactive flow:

```text
Token name:
OpenCode Local

Scopes:
[x] project:read
[x] task:read
[x] task:draft:write
[ ] note:read
[ ] note:write

Expiration:
30 days
```

Output:

```text
MCP token created.

IMPORTANT:
This token will only be displayed once.

mcp_xxxxxxxxxxxxxxxxxxxxxxxxx
```

Do not write the raw token to logs.

---

# 37. MCP Token Admin UI

Create:

```text
/mcp/tokens
```

Show:

```text
Name
Scopes
Created
Expires
Last used
Status
```

Actions:

```text
Create
Revoke
```

Never show the full token after creation.

Display only:

```text
mcp_live_************
```

---

# 38. MCP Audit UI

Create:

```text
/mcp/audit-log
```

Display:

```text
Date
Tool
Action
Token
Success
Operation ID
```

Allow filtering by:

```text
tool
success
token
date
```

Never display secret values.

---

# 39. Security Requirements

The following are mandatory:

- Hash MCP tokens.
- Never log raw tokens.
- Use explicit scopes.
- Validate every MCP input.
- Use authorization before data access.
- Use transactions for sensitive mutations.
- Use idempotency for writes.
- Use optimistic concurrency for editable drafts.
- Rate-limit MCP.
- Support token revocation.
- Support token expiration.
- Keep AI writes draft-only.
- Require human approval for publishing.
- Do not expose internal exceptions.
- Do not expose arbitrary database queries.
- Do not expose a generic "execute PHP" tool.
- Do not expose a generic "execute SQL" tool.
- Do not give MCP direct unrestricted Eloquent access.

---

# 40. Logging

Log useful technical information:

```text
mcp request id
tool
token id
operation id
duration
success
error code
```

Never log:

```text
Authorization header
raw token
password
API secret
```

Use a request correlation ID where practical.

---

# 41. Code Quality

Use:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
npm run build
```

All should pass before merging.

Use strict PHP typing where appropriate:

```php
declare(strict_types=1);
```

Use:

- final classes where appropriate
- readonly DTOs where appropriate
- enums for fixed values
- explicit return types
- dependency injection
- small application services
- Laravel conventions

Avoid overengineering.

---

# 42. GitHub Workflow

Create:

```text
.github/
├── ISSUE_TEMPLATE/
│   ├── bug.yml
│   ├── feature.yml
│   └── task.yml
├── workflows/
│   ├── tests.yml
│   └── pr-checks.yml
└── pull_request_template.md
```

Pull requests must run:

```text
PHP
├── Composer install
├── Pint
├── PHPStan
└── PHPUnit

Frontend
└── npm build
```

---

# 43. Implementation Phases

Do not implement everything at once.

## Phase 1 — Laravel foundation

Implement:

- Laravel 13
- authentication
- Inertia/Vue
- Tailwind
- MySQL
- User model
- basic dashboard

Verify:

```bash
php artisan test
npm run build
```

---

## Phase 2 — Domain

Implement:

- Project
- Task
- TaskDraft
- Note
- relationships
- factories
- seeders

Build basic CRUD UI.

---

## Phase 3 — MCP token infrastructure

Implement:

- McpToken
- scopes
- token generation
- hashing
- revocation
- expiration
- Artisan token command
- admin UI

Write tests.

---

## Phase 4 — MCP server

Implement:

```text
/api/mcp
```

Implement:

- authentication
- MCP context
- tool registry
- tool discovery
- structured errors

First tool:

```text
projects:list
```

Test it with MCP Inspector.

---

## Phase 5 — Read tools

Implement:

```text
projects:list
projects:get
tasks:list
tasks:get
```

Add scope checking.

---

## Phase 6 — Draft writes

Implement:

```text
tasks:create-draft
tasks:update-draft
```

Add:

- operation IDs
- idempotency
- optimistic concurrency
- audit logs

---

## Phase 7 — Human approval

Implement:

```text
approve draft
reject draft
```

Ensure MCP cannot publish directly.

---

## Phase 8 — OpenCode

Configure OpenCode as a remote MCP client.

Test:

```text
"List my projects."
```

Then:

```text
"Show me the high priority tasks."
```

Then:

```text
"Create a draft task to add an MCP audit log viewer."
```

Verify that the result is a draft and not a published task.

---

## Phase 9 — Security hardening

Implement and test:

- token expiration
- token revocation
- scope isolation
- rate limiting
- origin validation
- audit logging
- error sanitization

---

## Phase 10 — GitHub integration

Only after the core MCP server is stable.

Add optional tools:

```text
github:issues:list
github:issues:get
github:issues:create
```

Do not give the AI unrestricted GitHub access.

Use a separate GitHub integration/token with explicit permissions.

---

# 44. Definition of Done

The project is complete when all of the following work:

### Authentication

```text
OpenCode
    |
    | Bearer token
    v
Laravel MCP
    |
    v
Authenticated MCP context
```

### Discovery

```text
tools/list
```

returns available authorized tools.

### Reading

OpenCode can:

```text
list projects
get project
list tasks
get task
```

### Writing

OpenCode can:

```text
create task draft
update task draft
```

but cannot publish directly.

### Idempotency

Retrying a write with the same operation ID does not duplicate data.

### Concurrency

Stale draft updates are rejected.

### Human approval

A human can:

```text
approve
reject
```

a draft.

### Audit

Every MCP operation produces an audit entry.

### Security

Tokens can be:

```text
created
expired
revoked
scoped
```

### Client compatibility

The MCP endpoint works with:

```text
MCP Inspector
OpenCode
```

### Quality

All tests pass:

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run build
```

---

# 45. Final Architecture

The final project should look approximately like:

```text
app/
├── Application/
│   ├── Projects/
│   ├── Tasks/
│   └── Notes/
│
├── Domain/
│   ├── Projects/
│   ├── Tasks/
│   └── Notes/
│
├── Http/
│   └── Controllers/
│
├── Models/
│   ├── Project.php
│   ├── Task.php
│   ├── TaskDraft.php
│   ├── Note.php
│   ├── McpToken.php
│   ├── McpOperation.php
│   └── McpAuditLog.php
│
└── Mcp/
    ├── Auth/
    ├── Server/
    ├── Tools/
    └── Exceptions/

routes/
├── web.php
└── api.php

resources/
├── js/
│   ├── pages/
│   ├── components/
│   └── layouts/
└── ...

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
│   └── Mcp/
└── Unit/
    └── Mcp/

.github/
├── ISSUE_TEMPLATE/
├── workflows/
└── pull_request_template.md
```

---

# 46. OpenCode Implementation Rules

When implementing this project:

1. Inspect the existing Laravel installation before creating files.
2. Do not overwrite existing application configuration unnecessarily.
3. Follow Laravel 13 conventions.
4. Prefer small, focused classes.
5. Keep MCP adapters thin.
6. Keep business logic in application/domain services.
7. Do not put business logic in controllers.
8. Do not put business logic directly into MCP protocol handlers.
9. Use dependency injection.
10. Use explicit DTOs/input objects for MCP input.
11. Use enums for scopes and statuses.
12. Add tests for every new MCP tool.
13. Run formatting after implementation.
14. Run static analysis.
15. Run the complete test suite.
16. Do not commit secrets.
17. Never expose raw MCP tokens in logs.
18. Never implement unrestricted SQL/PHP execution tools.
19. Never allow MCP to bypass authorization.
20. Never allow MCP to publish drafts directly.
21. Prefer human approval for destructive or externally visible actions.

---

# 47. First Implementation Task

Start with only Phase 1.

After Phase 1 is complete, stop and verify:

```bash
php artisan test
npm run build
```

Then implement Phase 2.

Do not jump directly to the complete MCP server.

Build the system incrementally so each layer can be tested independently.

The first MCP milestone should ultimately be:

```text
OpenCode
    |
    | "List my projects"
    v
POST /api/mcp
    |
    v
Authenticate token
    |
    v
Check project:read
    |
    v
projects:list
    |
    v
ListProjects application service
    |
    v
Project model
    |
    v
MySQL
```

Once this works reliably, continue with the draft/write workflow.
