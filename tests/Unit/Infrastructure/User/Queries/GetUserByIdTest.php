<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\User\Queries;

use Icarus\Domain\User\ReadModels\UserResult;
use Icarus\Domain\User\UserId;
use Icarus\Infrastructure\User\Queries\GetUserById;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('infrastructure'), Group('user')]
class GetUserByIdTest extends TestCase
{
    private ConnectionInterface&MockInterface $connection;

    private GetUserById $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->query      = new GetUserById($this->connection);
    }

    private function mockBuilder(?object $result): void
    {
        $builder = Mockery::mock(Builder::class);

        $this->connection->shouldReceive('table')
                         ->with('users')
                         ->once()
                         ->andReturn($builder);

        $builder->shouldReceive('select')
                ->with(['name', 'email', 'verified_at'])
                ->once()
                ->andReturnSelf();

        $builder->shouldReceive('where')
                ->with('id', Mockery::type(UserId::class))
                ->once()
                ->andReturnSelf();

        $builder->shouldReceive('first')
                ->once()
                ->andReturn($result);
    }

    #[Test]
    public function executeReturnsNullWhenUserNotFound(): void
    {
        $this->mockBuilder(null);

        $result = $this->query->execute(UserId::generate());

        $this->assertNull($result);
    }

    #[Test]
    public function executeReturnsUserResultWhenFound(): void
    {
        $userId = UserId::generate();

        $this->mockBuilder((object) [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'verified_at' => null,
        ]);

        $result = $this->query->execute($userId);

        $this->assertInstanceOf(UserResult::class, $result);
        $this->assertSame($userId, $result->userId);
        $this->assertSame('Test User', $result->name);
        $this->assertSame('test@example.com', $result->email);
        $this->assertNull($result->verifiedAt);
    }

    #[Test]
    public function executeHydratesVerifiedAtWhenPresent(): void
    {
        $userId = UserId::generate();

        $this->mockBuilder((object) [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'verified_at' => '1988-06-24 02:05:07',
        ]);

        $result = $this->query->execute($userId);

        $this->assertInstanceOf(UserResult::class, $result);
        $this->assertNotNull($result->verifiedAt);
        $this->assertSame(1988, $result->verifiedAt->year);
        $this->assertSame(6, $result->verifiedAt->month);
        $this->assertSame(24, $result->verifiedAt->day);
        $this->assertSame(2, $result->verifiedAt->hour);
        $this->assertSame(5, $result->verifiedAt->minute);
        $this->assertSame(7, $result->verifiedAt->second);
    }
}
