<?php

declare(strict_types=1);

namespace App\Models;

use App\Mcp\Auth\McpScope;
use Database\Factories\McpTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $token_hash
 * @property string $token_prefix
 * @property int|null $created_by
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'token_hash', 'token_prefix', 'created_by', 'expires_at', 'last_used_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
class McpToken extends Model
{
    /** @use HasFactory<McpTokenFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<McpTokenScope, $this>
     */
    public function scopes(): HasMany
    {
        return $this->hasMany(McpTokenScope::class);
    }

    /**
     * @return HasMany<McpOperation, $this>
     */
    public function operations(): HasMany
    {
        return $this->hasMany(McpOperation::class);
    }

    /**
     * @return HasMany<McpAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(McpAuditLog::class);
    }

    /**
     * @return list<McpScope>
     */
    public function scopeEnumValues(): array
    {
        $scopes = [];

        foreach ($this->scopes()->get() as $scope) {
            $scopes[] = $scope->scope;
        }

        return $scopes;
    }

    public function hasScope(McpScope $scope): bool
    {
        return in_array($scope, $this->scopeEnumValues(), true);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $reference = $now ?? Carbon::now();

        return $this->expires_at !== null && $this->expires_at->lt($reference);
    }

    /**
     * The masked token as shown in the admin UI, e.g. mcp_live_*****.
     */
    public function maskedToken(): string
    {
        return $this->token_prefix.'*****';
    }
}
