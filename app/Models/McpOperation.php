<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\McpOperationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $mcp_token_id
 * @property string $operation_id
 * @property string $operation
 * @property McpOperationStatus $status
 * @property array<string, mixed>|null $result
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['mcp_token_id', 'operation_id', 'operation', 'status', 'result'])]
class McpOperation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => McpOperationStatus::class,
            'result' => 'array',
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
