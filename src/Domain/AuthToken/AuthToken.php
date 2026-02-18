<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\HasEvents;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\Shared\RecordsEvents;
use Icarus\Domain\User\UserId;

final class AuthToken implements RecordsEvents
{
    use HasEvents;

    public static function create(
        UserId           $userId,
        OperatingContext $context,
        StoredToken      $token,
        Device           $device = new Device(),
        ?CarbonImmutable $lastUsedAt = null,
        ?CarbonImmutable $expiresAt = null
    ): self
    {
        return new self(
            AuthTokenId::generate(),
            $token,
            $userId,
            $context,
            $device,
            $lastUsedAt,
            $expiresAt
        );
    }

    public readonly AuthTokenId $id;

    public readonly StoredToken $token;

    public readonly UserId $userId;

    public readonly OperatingContext $context;

    public readonly Device $device;

    private(set) ?CarbonImmutable $lastUsedAt;

    private(set) ?CarbonImmutable $expiresAt;

    private(set) ?CarbonImmutable $revokedAt;

    private(set) ?string $revokedReason;

    public function __construct(
        AuthTokenId      $id,
        StoredToken      $token,
        UserId           $userId,
        OperatingContext $context,
        Device           $device = new Device(),
        ?CarbonImmutable $lastUsedAt = null,
        ?CarbonImmutable $expiresAt = null,
        ?CarbonImmutable $revokedAt = null,
        ?string          $revokedReason = null
    )
    {
        $this->id            = $id;
        $this->token         = $token;
        $this->userId        = $userId;
        $this->context       = $context;
        $this->device        = $device;
        $this->lastUsedAt    = $lastUsedAt;
        $this->expiresAt     = $expiresAt;
        $this->revokedAt     = $revokedAt;
        $this->revokedReason = $revokedReason;
    }

    public function hasExpired(CarbonImmutable $now): bool
    {
        return $this->expiresAt && $this->expiresAt->isBefore($now);
    }

    public function wasRevoked(CarbonImmutable $now): bool
    {
        return $this->revokedAt && $this->revokedAt->isBefore($now);
    }

    public function revoke(?string $reason = null): void
    {
        $this->revokedAt     = CarbonImmutable::now();
        $this->revokedReason = $reason;
    }

    public function updateUsedAt(CarbonImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
