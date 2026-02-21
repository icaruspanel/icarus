<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\DataObjects;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;

final readonly class AuthTokenResult
{
    public function __construct(
        public AuthTokenId      $authTokenId,
        public UserId           $userId,
        public StoredToken      $token,
        public OperatingContext $context,
        public ?CarbonImmutable $expiresAt = null,
        public ?CarbonImmutable $revokedAt = null
    )
    {
    }

    public function hasExpired(CarbonImmutable $now): bool
    {
        return $this->expiresAt && $this->expiresAt->lte($now);
    }

    public function wasRevoked(CarbonImmutable $now): bool
    {
        return $this->revokedAt && $this->revokedAt->lte($now);
    }
}
