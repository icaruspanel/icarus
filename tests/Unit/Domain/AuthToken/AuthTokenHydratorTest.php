<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken;

use Icarus\Domain\AuthToken\AuthTokenHydrator;
use Icarus\Domain\Shared\OperatingContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('auth-token')]
class AuthTokenHydratorTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private static array $dummyData;

    protected function setUp(): void
    {
        parent::setUp();

        self::$dummyData = [
            'id'             => Ulid::generate(),
            'user_id'        => Ulid::generate(),
            'context'        => 'account',
            'selector'       => 'selector',
            'secret'         => 'secret',
            'user_agent'     => 'user-agent',
            'ip'             => '127.0.0.1',
            'last_used_at'   => '1988-06-24 02:05:07',
            'expires_at'     => '1988-06-24 02:05:08',
            'revoked_at'     => '1988-06-24 02:05:09',
            'revoked_reason' => 'Testing purposes',
        ];
    }

    #[Test]
    public function createsAuthTokenWithTheCorrectId(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['id'], $token->id->id);
    }

    #[Test]
    public function createsAuthTokenWithTheCorrectUserId(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['user_id'], $token->userId->id);
    }

    #[Test]
    public function createsAuthTokenWithTheCorrectContext(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(OperatingContext::from(self::$dummyData['context']), $token->context);
    }

    #[Test]
    public function createsAuthTokenWithTheCorrectStoredToken(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['selector'], $token->token->selector);
        $this->assertSame(self::$dummyData['secret'], $token->token->secret);
    }

    #[Test]
    public function createsAuthTokenWithTheCorrectDevice(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['user_agent'], $token->device->userAgent);
        $this->assertSame(self::$dummyData['ip'], $token->device->ip);
    }

    #[Test]
    public function createsAuthTokenWithTheCorrectTimestamps(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token = $hydrator->hydrate(self::$dummyData);

        $this->assertNotNull($token->lastUsedAt);
        $this->assertSame(1988, $token->lastUsedAt->year);
        $this->assertSame(6, $token->lastUsedAt->month);
        $this->assertSame(24, $token->lastUsedAt->day);
        $this->assertSame(2, $token->lastUsedAt->hour);
        $this->assertSame(5, $token->lastUsedAt->minute);
        $this->assertSame(7, $token->lastUsedAt->second);

        $this->assertNotNull($token->expiresAt);
        $this->assertSame(1988, $token->expiresAt->year);
        $this->assertSame(6, $token->expiresAt->month);
        $this->assertSame(24, $token->expiresAt->day);
        $this->assertSame(2, $token->expiresAt->hour);
        $this->assertSame(5, $token->expiresAt->minute);
        $this->assertSame(8, $token->expiresAt->second);

        $this->assertNotNull($token->revokedAt);
        $this->assertSame(1988, $token->revokedAt->year);
        $this->assertSame(6, $token->revokedAt->month);
        $this->assertSame(24, $token->revokedAt->day);
        $this->assertSame(2, $token->revokedAt->hour);
        $this->assertSame(5, $token->revokedAt->minute);
        $this->assertSame(9, $token->revokedAt->second);
    }

    #[Test]
    public function createsAuthTokenWithNulls(): void
    {
        $hydrator = new AuthTokenHydrator();

        $data = self::$dummyData;

        $data['user_agent'] = $data['ip'] = $data['last_used_at'] = $data['expires_at'] = $data['revoked_at'] = $data['revoked_reason'] = null;
        $token              = $hydrator->hydrate($data);

        $this->assertSame($data['user_agent'], $token->device->userAgent);
        $this->assertSame($data['ip'], $token->device->ip);
        $this->assertSame($data['last_used_at'], $token->lastUsedAt);
        $this->assertSame($data['expires_at'], $token->expiresAt);
        $this->assertSame($data['revoked_at'], $token->revokedAt);
        $this->assertSame($data['revoked_reason'], $token->revokedReason);

        $this->assertNull($token->device->userAgent);
        $this->assertNull($token->device->ip);
        $this->assertNull($token->lastUsedAt);
        $this->assertNull($token->expiresAt);
        $this->assertNull($token->revokedAt);
        $this->assertNull($token->revokedReason);
    }

    #[Test]
    public function dehydrateReturnsCorrectArrayKeys(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token  = $hydrator->hydrate(self::$dummyData);
        $result = $hydrator->dehydrate($token);

        $expectedKeys = [
            'id', 'user_id', 'context', 'selector', 'secret',
            'user_agent', 'ip', 'last_used_at', 'expires_at',
            'revoked_at', 'revoked_reason',
        ];

        $this->assertSame($expectedKeys, array_keys($result));
    }

    #[Test]
    public function dehydrateFormatsTimestampsCorrectly(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token  = $hydrator->hydrate(self::$dummyData);
        $result = $hydrator->dehydrate($token);

        $this->assertSame('1988-06-24 02:05:07', $result['last_used_at']);
        $this->assertSame('1988-06-24 02:05:08', $result['expires_at']);
        $this->assertSame('1988-06-24 02:05:09', $result['revoked_at']);
    }

    #[Test]
    public function dehydrateHandlesNullTimestamps(): void
    {
        $hydrator = new AuthTokenHydrator();

        $data = self::$dummyData;

        $data['last_used_at'] = $data['expires_at'] = $data['revoked_at'] = $data['revoked_reason'] = null;

        $token  = $hydrator->hydrate($data);
        $result = $hydrator->dehydrate($token);

        $this->assertNull($result['last_used_at']);
        $this->assertNull($result['expires_at']);
        $this->assertNull($result['revoked_at']);
        $this->assertNull($result['revoked_reason']);
    }

    #[Test]
    public function hydrateThenDehydrateReturnsOriginalData(): void
    {
        $hydrator = new AuthTokenHydrator();

        $token  = $hydrator->hydrate(self::$dummyData);
        $result = $hydrator->dehydrate($token);

        $this->assertSame(self::$dummyData, $result);
    }
}
