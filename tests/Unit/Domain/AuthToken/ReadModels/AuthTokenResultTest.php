<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken\ReadModels;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\ReadModels\AuthTokenResult;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('auth-token')]
class AuthTokenResultTest extends TestCase
{
    private function makeResult(
        ?CarbonImmutable $expiresAt = null,
        ?CarbonImmutable $revokedAt = null
    ): AuthTokenResult
    {
        return new AuthTokenResult(
            AuthTokenId::generate(),
            UserId::generate(),
            new StoredToken('selector', hash('sha256', 'secret')),
            OperatingContext::Account,
            $expiresAt,
            $revokedAt,
        );
    }

    // ——————————————————————————————————————————————
    // hasExpired
    // ——————————————————————————————————————————————

    #[Test]
    public function hasExpiredReturnsFalseWhenExpiresAtIsNull(): void
    {
        $result = $this->makeResult();

        $this->assertFalse($result->hasExpired(CarbonImmutable::now()));
    }

    #[Test]
    public function hasExpiredReturnsFalseWhenExpiresAtIsInTheFuture(): void
    {
        $now    = CarbonImmutable::now();
        $result = $this->makeResult(expiresAt: $now->addMonth());

        $this->assertFalse($result->hasExpired($now));
    }

    #[Test]
    public function hasExpiredReturnsTrueWhenExpiresAtIsInThePast(): void
    {
        $now    = CarbonImmutable::now();
        $result = $this->makeResult(expiresAt: $now->subMonth());

        $this->assertTrue($result->hasExpired($now));
    }

    #[Test]
    public function hasExpiredReturnsTrueWhenExpiresAtIsNow(): void
    {
        $now    = CarbonImmutable::now();
        $result = $this->makeResult(expiresAt: $now);

        $this->assertTrue($result->hasExpired($now));
    }

    // ——————————————————————————————————————————————
    // wasRevoked
    // ——————————————————————————————————————————————

    #[Test]
    public function wasRevokedReturnsFalseWhenRevokedAtIsNull(): void
    {
        $result = $this->makeResult();

        $this->assertFalse($result->wasRevoked(CarbonImmutable::now()));
    }

    #[Test]
    public function wasRevokedReturnsFalseWhenRevokedAtIsInTheFuture(): void
    {
        $now    = CarbonImmutable::now();
        $result = $this->makeResult(revokedAt: $now->addMonth());

        $this->assertFalse($result->wasRevoked($now));
    }

    #[Test]
    public function wasRevokedReturnsTrueWhenRevokedAtIsInThePast(): void
    {
        $now    = CarbonImmutable::now();
        $result = $this->makeResult(revokedAt: $now->subMonth());

        $this->assertTrue($result->wasRevoked($now));
    }

    #[Test]
    public function wasRevokedReturnsTrueWhenRevokedAtIsNow(): void
    {
        $now    = CarbonImmutable::now();
        $result = $this->makeResult(revokedAt: $now);

        $this->assertTrue($result->wasRevoked($now));
    }
}
