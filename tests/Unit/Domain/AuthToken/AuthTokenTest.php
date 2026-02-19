<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\AuthToken\Device;
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
        $tokenA  = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );
        $tokenB  = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertTrue(Ulid::isValid($tokenA->id->id));
        $this->assertTrue(Ulid::isValid($tokenB->id->id));
        $this->assertNotSame($tokenA->id->id, $tokenB->id->id);
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
    public function createAssignsTheGivenDevice(): void
    {
        $context = OperatingContext::Account;
        $device  = new Device('TestAgent', '127.0.0.1');
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
            $device,
        );

        $this->assertSame('TestAgent', $token->device->userAgent);
        $this->assertSame('127.0.0.1', $token->device->ip);
    }

    #[Test]
    public function createDefaultsOptionalFieldsToNull(): void
    {
        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertNull($token->lastUsedAt);
        $this->assertNull($token->expiresAt);
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
    public function wasRevokedReturnsFalseWithNoRevokedAtTimestamp(): void
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
    public function wasRevokedReturnsFalseWhenRevokedAtIsInTheFuture(): void
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
    public function wasRevokedReturnsTrueWhenRevokedAtIsInThePast(): void
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
    public function wasRevokedReturnsTrueWhenRevokedAtIsNow(): void
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
    public function revokeSetsRevokedAtToNow(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertNull($token->revokedAt);

        $token->revoke();

        $this->assertNotNull($token->revokedAt);
        $this->assertTrue($token->revokedAt->equalTo($now));
        $this->assertTrue($token->wasRevoked($now));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function revokeSetsRevokedReasonIfOneIsProvided(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertNull($token->revokedAt);
        $this->assertNull($token->revokedReason);

        $token->revoke('For testing purposes');

        $this->assertNotNull($token->revokedAt);
        $this->assertNotNull($token->revokedReason);
        $this->assertTrue($token->revokedAt->equalTo($now));
        $this->assertTrue($token->wasRevoked($now));
        $this->assertSame('For testing purposes', $token->revokedReason);

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function revokeDefaultsReasonToNull(): void
    {
        $now = CarbonImmutable::now();

        CarbonImmutable::setTestNow($now);

        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
        );

        $this->assertNull($token->revokedReason);

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

        $context = OperatingContext::Account;
        $token   = AuthToken::create(
            UserId::generate(),
            $context,
            StoredToken::create($context)->token,
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
    }
}
