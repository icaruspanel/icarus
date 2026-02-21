<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel\Auth;

use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use Icarus\Kernel\Auth\AuthenticatedUser;
use Icarus\Kernel\Auth\AuthTokenGuard;
use Icarus\Kernel\AuthToken\Actions\FlagAuthTokenUsage;
use Icarus\Kernel\User\Actions\GetUserById;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('kernel'), Group('auth')]
class AuthTokenGuardTest extends TestCase
{
    private ConnectionInterface&MockInterface $connection;

    private AuthTokenRepository&MockInterface $tokenRepository;

    private GetUserById $userResolver;

    private FlagAuthTokenUsage $flagTokenUsage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection      = Mockery::mock(ConnectionInterface::class);
        $this->tokenRepository = Mockery::mock(AuthTokenRepository::class);

        $this->userResolver   = new GetUserById($this->connection);
        $this->flagTokenUsage = new FlagAuthTokenUsage($this->tokenRepository);
    }

    private function makeAuthContext(): AuthContext
    {
        return new AuthContext(
            UserId::generate(),
            AuthTokenId::generate(),
            OperatingContext::Account,
        );
    }

    private function makeGuard(?AuthContext $authContext = null): AuthTokenGuard
    {
        if ($authContext !== null) {
            // Constructor calls setAuthContext, which calls flagTokenUsage
            $this->tokenRepository
                ->shouldReceive('find')
                ->with($authContext->authTokenId)
                ->once()
                ->andReturnNull();
        }

        return new AuthTokenGuard(
            'auth-token',
            $this->userResolver,
            $this->flagTokenUsage,
            $authContext,
        );
    }

    private function mockUserQuery(UserId $userId, ?object $result): void
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
                ->with('id', $userId)
                ->once()
                ->andReturnSelf();

        $builder->shouldReceive('first')
                ->once()
                ->andReturn($result);
    }

    // ——————————————————————————————————————————————
    // constructor
    // ——————————————————————————————————————————————

    #[Test]
    public function constructorSetsName(): void
    {
        $guard = $this->makeGuard();

        $this->assertSame('auth-token', $guard->name);
    }

    #[Test]
    public function constructorSetsAuthContextToNull(): void
    {
        $guard = $this->makeGuard();

        $this->assertNull($guard->authContext);
    }

    #[Test]
    public function constructorFlagsTokenUsageWhenAuthContextProvided(): void
    {
        $context = $this->makeAuthContext();

        // The token repository expectation is set inside makeGuard —
        // it receives a find() call from FlagAuthTokenUsageHandler
        $guard = $this->makeGuard($context);

        $this->assertSame($context, $guard->authContext);
    }

    // ——————————————————————————————————————————————
    // setAuthContext
    // ——————————————————————————————————————————————

    #[Test]
    public function setAuthContextFlagsTokenUsage(): void
    {
        $guard   = $this->makeGuard();
        $context = $this->makeAuthContext();

        // FlagAuthTokenUsageHandler will call find() on the token repository
        $this->tokenRepository
            ->shouldReceive('find')
            ->with($context->authTokenId)
            ->once()
            ->andReturnNull();

        $guard->setAuthContext($context);

        $this->assertSame($context, $guard->authContext);
    }

    #[Test]
    public function setAuthContextWithNullDoesNotFlagUsage(): void
    {
        $guard = $this->makeGuard();

        // tokenRepository should not receive any calls
        $this->tokenRepository->shouldNotReceive('find');

        $guard->setAuthContext(null);

        $this->assertNull($guard->authContext);
    }

    // ——————————————————————————————————————————————
    // user
    // ——————————————————————————————————————————————

    #[Test]
    public function userReturnsNullWhenNoAuthContext(): void
    {
        $guard = $this->makeGuard();

        $this->assertNull($guard->user());
    }

    #[Test]
    public function userResolvesAuthenticatedUserFromAuthContext(): void
    {
        $context = $this->makeAuthContext();
        $guard   = $this->makeGuard($context);

        $this->mockUserQuery($context->userId, (object) [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'verified_at' => null,
        ]);

        $user = $guard->user();

        $this->assertInstanceOf(AuthenticatedUser::class, $user);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    #[Test]
    public function userReturnsNullWhenUserNotFound(): void
    {
        $context = $this->makeAuthContext();
        $guard   = $this->makeGuard($context);

        $this->mockUserQuery($context->userId, null);

        $this->assertNull($guard->user());
    }

    #[Test]
    public function userCachesResultOnSubsequentCalls(): void
    {
        $context = $this->makeAuthContext();
        $guard   = $this->makeGuard($context);

        // The query should only execute once
        $this->mockUserQuery($context->userId, (object) [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'verified_at' => null,
        ]);

        $first  = $guard->user();
        $second = $guard->user();

        $this->assertSame($first, $second);
    }

    // ——————————————————————————————————————————————
    // validate
    // ——————————————————————————————————————————————

    #[Test]
    public function validateAlwaysReturnsFalse(): void
    {
        $guard = $this->makeGuard();

        $this->assertFalse($guard->validate(['email' => 'test@example.com', 'password' => 'secret']));
    }
}
