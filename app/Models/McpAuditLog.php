<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $mcp_token_id
 * @property string|null $operation_id
 * @property string $tool_name
 * @property string $action
 * @property string|null $scope
 * @property string|null $input_hash
 * @property bool $success
 * @property string|null $error_code
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable([
    'mcp_token_id',
    'operation_id',
    'tool_name',
    'action',
    'scope',
    'input_hash',
    'success',
    'error_code',
    'metadata',
    'created_at',
])]
class McpAuditLog extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<McpToken, $this>
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(McpToken::class, 'mcp_token_id');
    }
}
