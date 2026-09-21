<?php

declare(strict_types=1);

namespace App\Models;

use App\Mcp\Auth\McpScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $mcp_token_id
 * @property McpScope $scope
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['mcp_token_id', 'scope'])]
class McpTokenScope extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => McpScope::class,
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
