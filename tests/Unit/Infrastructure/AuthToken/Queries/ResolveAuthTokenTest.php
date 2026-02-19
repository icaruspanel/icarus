<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\AuthToken\Queries;

use Icarus\Domain\AuthToken\ReadModels\AuthTokenResult;
use Icarus\Infrastructure\AuthToken\Queries\ResolveAuthToken;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('infrastructure'), Group('auth-token')]
class ResolveAuthTokenTest extends TestCase
{
    private ConnectionInterface&MockInterface $connection;

    private ResolveAuthToken $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->query      = new ResolveAuthToken($this->connection);
    }

    private function mockBuilder(?object $result): void
    {
        $builder = Mockery::mock(Builder::class);

        $this->connection->shouldReceive('table')
                         ->with('auth_tokens')
                         ->once()
                         ->andReturn($builder);

        $builder->shouldReceive('select')
                ->with([
                    'id', 'user_id', 'selector', 'secret',
                    'context', 'expires_at', 'revoked_at',
                ])
                ->once()
                ->andReturnSelf();

        $builder->shouldReceive('where')
                ->with('selector', '=', Mockery::type('string'))
                ->once()
                ->andReturnSelf();

        $builder->shouldReceive('first')
                ->once()
                ->andReturn($result);
    }

    #[Test]
    public function executeReturnsNullWhenTokenNotFound(): void
    {
        $this->mockBuilder(null);

        $result = $this->query->execute('abcd1234');

        $this->assertNull($result);
    }

    #[Test]
    public function executeReturnsAuthTokenResultWhenFound(): void
    {
        $id     = Ulid::generate();
        $userId = Ulid::generate();

        $this->mockBuilder((object) [
            'id'         => $id,
            'user_id'    => $userId,
            'selector'   => 'abcd1234',
            'secret'     => hash('sha256', 'secret'),
            'context'    => 'account',
            'expires_at' => null,
            'revoked_at' => null,
        ]);

        $result = $this->query->execute('abcd1234');

        $this->assertInstanceOf(AuthTokenResult::class, $result);
        $this->assertSame($id, $result->authTokenId->id);
        $this->assertSame($userId, $result->userId->id);
        $this->assertSame('abcd1234', $result->token->selector);
        $this->assertSame(hash('sha256', 'secret'), $result->token->secret);
        $this->assertSame('account', $result->context->value);
        $this->assertNull($result->expiresAt);
        $this->assertNull($result->revokedAt);
    }

    #[Test]
    public function executeHydratesExpiresAtWhenPresent(): void
    {
        $this->mockBuilder((object) [
            'id'         => Ulid::generate(),
            'user_id'    => Ulid::generate(),
            'selector'   => 'abcd1234',
            'secret'     => hash('sha256', 'secret'),
            'context'    => 'account',
            'expires_at' => '1988-06-24 02:05:07',
            'revoked_at' => null,
        ]);

        $result = $this->query->execute('abcd1234');

        $this->assertNotNull($result->expiresAt);
        $this->assertSame(1988, $result->expiresAt->year);
        $this->assertSame(6, $result->expiresAt->month);
        $this->assertSame(24, $result->expiresAt->day);
        $this->assertSame(2, $result->expiresAt->hour);
        $this->assertSame(5, $result->expiresAt->minute);
        $this->assertSame(7, $result->expiresAt->second);
        $this->assertNull($result->revokedAt);
    }

    #[Test]
    public function executeHydratesRevokedAtWhenPresent(): void
    {
        $this->mockBuilder((object) [
            'id'         => Ulid::generate(),
            'user_id'    => Ulid::generate(),
            'selector'   => 'abcd1234',
            'secret'     => hash('sha256', 'secret'),
            'context'    => 'platform',
            'expires_at' => null,
            'revoked_at' => '1988-06-24 02:05:09',
        ]);

        $result = $this->query->execute('abcd1234');

        $this->assertSame('platform', $result->context->value);
        $this->assertNull($result->expiresAt);
        $this->assertNotNull($result->revokedAt);
        $this->assertSame(1988, $result->revokedAt->year);
        $this->assertSame(6, $result->revokedAt->month);
        $this->assertSame(24, $result->revokedAt->day);
        $this->assertSame(2, $result->revokedAt->hour);
        $this->assertSame(5, $result->revokedAt->minute);
        $this->assertSame(9, $result->revokedAt->second);
    }
}
