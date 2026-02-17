<?php

namespace App\Models;

use App\Enum\UserType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAuthToken
 */
class AuthToken extends Model
{
    /** @use HasFactory<\Database\Factories\AuthTokenFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        parent::booted();

        // When creating a new record, if there's no token, generate one
        static::creating(static function (AuthToken $model) {
            if ($model->getAttribute('token') === null) {
                $model->generateToken();
            }
        });
    }

    protected $primaryKey = 'token';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'auth_tokens';

    protected $fillable = [
        'token',
        'type',
        'device',
        'ip',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'revoked_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'         => UserType::class,
            'expires_at'   => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at'   => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasExpired(CarbonImmutable $now): bool
    {
        return $this->expires_at && $this->expires_at->isBefore($now);
    }

    public function wasRevoked(CarbonImmutable $now): bool
    {
        return $this->revoked_at && $this->revoked_at->isBefore($now);
    }

    /**
     * @throws \Random\RandomException
     */
    public function generateToken(): void
    {
        $this->token = $this->type->prefix() . bin2hex(random_bytes(16));
    }
}
