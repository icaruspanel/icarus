<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('auth-token')]
class AuthTokenTest extends TestCase
{
    #[Test]
    public function createGeneratesUniqueIds(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertTrue(Ulid::isValid($token->id->id));
    }

    #[Test]
    public function createAssignsTheGivenUserId(): void
    {
        $context = OperatingContext::Account;
        $userId  = UserId::generate();
        $token   = AuthToken::create(
            $userId,
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertSame($userId->id, $token->userId->id);
    }

    #[Test]
    public function createAssignsTheCorrectOperatingContext(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertEquals($context, $token->context);
    }

    #[Test]
    public function createDefaultsRevokedFieldsToNull(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertNull($token->revokedAt);
        $this->assertNull($token->revokedReason);
    }

    #[Test]
    public function hasExpiredReturnsFalseWithNoExpiresAtTimestamp(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertFalse($token->hasExpired(CarbonImmutable::now()));
    }

    #[Test]
    public function hasExpiredReturnsFalseWhenExpiresAtIsInTheFuture(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
            expiresAt: CarbonImmutable::now()->addMonth()
        );

        $this->assertNotNull($token->expiresAt);
        $this->assertTrue($token->expiresAt->isFuture());
        $this->assertFalse($token->hasExpired(CarbonImmutable::now()));
    }

    #[Test]
    public function hasExpiredReturnsTrueWhenExpiresAtIsInThePast(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
            expiresAt: CarbonImmutable::now()->subMonth()
        );

        $this->assertNotNull($token->expiresAt);
        $this->assertTrue($token->expiresAt->isPast());
        $this->assertTrue($token->hasExpired(CarbonImmutable::now()));
    }

    #[Test]
    public function hasExpiredReturnsTrueWhenExpiresAtIsNow(): void
    {
        $now     = CarbonImmutable::now();
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
            expiresAt: $now
        );

        $this->assertNotNull($token->expiresAt);
        $this->assertTrue($token->expiresAt->isPast());
        $this->assertTrue($token->hasExpired($now));
    }

    #[Test]
    public function wasRevokedReturnsFalseWithNoExpiresAtTimestamp(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertFalse($token->wasRevoked(CarbonImmutable::now()));
    }

    #[Test]
    public function wasRevokedReturnsFalseWhenExpiresAtIsInTheFuture(): void
    {
        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: CarbonImmutable::now()->addMonth()
        );

        $this->assertNotNull($token->revokedAt);
        $this->assertTrue($token->revokedAt->isFuture());
        $this->assertFalse($token->wasRevoked(CarbonImmutable::now()));
    }

    #[Test]
    public function wasRevokedReturnsTrueWhenExpiresAtIsInThePast(): void
    {
        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: CarbonImmutable::now()->subMonth()
        );

        $this->assertNotNull($token->revokedAt);
        $this->assertTrue($token->revokedAt->isPast());
        $this->assertTrue($token->wasRevoked(CarbonImmutable::now()));
    }

    #[Test]
    public function wasRevokedReturnsTrueWhenExpiresAtIsNow(): void
    {
        $now     = CarbonImmutable::now();
        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: $now
        );

        $this->assertNotNull($token->revokedAt);
        $this->assertTrue($token->revokedAt->isPast());
        $this->assertTrue($token->wasRevoked($now));
    }

    #[Test]
    public function revokedSetsRevokedAtToNow(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: $now
        );

        $token->revoke();

        $this->assertNotNull($token->revokedAt);
        $this->assertTrue($token->revokedAt->equalTo($now));
        $this->assertTrue($token->wasRevoked($now));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function revokedSetsRevokedReasonIfOneIsProvided(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: $now
        );

        $token->revoke('For testing purposes');

        $this->assertNotNull($token->revokedAt);
        $this->assertNotNull($token->revokedReason);
        $this->assertTrue($token->revokedAt->equalTo($now));
        $this->assertTrue($token->wasRevoked($now));
        $this->assertSame('For testing purposes', $token->revokedReason);

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function revokedDefaultsReasonToNull(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: $now
        );

        $token->revoke();

        $this->assertNotNull($token->revokedAt);
        $this->assertNull($token->revokedReason);
        $this->assertTrue($token->revokedAt->equalTo($now));
        $this->assertTrue($token->wasRevoked($now));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function updateUsedAtSetsLastUsedAtToNow(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = new AuthToken(
            AuthTokenId::generate(),
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
            revokedAt: $now
        );

        $this->assertNull($token->lastUsedAt);

        $token->updateUsedAt($now);

        $this->assertNotNull($token->lastUsedAt);
        $this->assertTrue($token->lastUsedAt->equalTo($now));

        $newNow = $now->subDay();

        $token->updateUsedAt($newNow);

        $this->assertNotNull($token->lastUsedAt);
        $this->assertTrue($token->lastUsedAt->equalTo($newNow));
        $this->assertTrue($token->lastUsedAt->isBefore($now));

        CarbonImmutable::setTestNow();
    }
}
