<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel\AuthToken\Actions;

use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\AuthToken\DataObjects\AuthenticationResult;
use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\AuthToken\Events\UserAuthenticated;
use Icarus\Domain\AuthToken\Exceptions\InvalidCredentials;
use Icarus\Domain\AuthToken\Exceptions\UnableToAuthenticate;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAttempting;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAuthorising;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\HashedPassword;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserEmail;
use Icarus\Domain\User\UserId;
use Icarus\Domain\User\UserRepository;
use Icarus\Kernel\AuthToken\Actions\AuthenticateUser;
use Icarus\Kernel\Contracts\EventDispatcher;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('kernel'), Group('auth-token')]
class AuthenticateUserTest extends TestCase
{
    private AuthTokenRepository&MockInterface $authTokenRepository;

    private UserRepository&MockInterface $userRepository;

    private EventDispatcher&MockInterface $dispatcher;

    private AuthenticateUser $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authTokenRepository = Mockery::mock(AuthTokenRepository::class);
        $this->userRepository      = Mockery::mock(UserRepository::class);
        $this->dispatcher          = Mockery::mock(EventDispatcher::class);

        $this->handler = new AuthenticateUser(
            $this->authTokenRepository,
            $this->userRepository,
            $this->dispatcher,
        );
    }

    private function makeUser(
        string $email = 'test@example.com',
        string $password = 'password',
        array  $operatesIn = [OperatingContext::Account],
        bool   $active = true,
    ): User
    {
        return new User(
            UserId::generate(),
            'Test User',
            UserEmail::unverified($email),
            HashedPassword::from($password),
            $operatesIn,
            $active,
        );
    }

    private function allowGates(): void
    {
        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAuthorising::class))
                         ->once();
    }

    // ——————————————————————————————————————————————
    // execute — success
    // ——————————————————————————————————————————————

    #[Test]
    public function executeReturnsAuthenticationResultOnSuccess(): void
    {
        $user = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->authTokenRepository->shouldReceive('save')
                                  ->with(Mockery::type(AuthToken::class))
                                  ->once()
                                  ->andReturnTrue();

        $this->allowGates();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(UserAuthenticated::class))
                         ->once();

        $result = $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );

        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertSame($user->id->id, $result->userId->id);
        $this->assertSame(OperatingContext::Account, $result->context);
        $this->assertNotEmpty($result->token);
    }

    #[Test]
    public function executeDispatchesUserAuthenticatedEvent(): void
    {
        $user = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->authTokenRepository->shouldReceive('save')
                                  ->with(Mockery::type(AuthToken::class))
                                  ->once()
                                  ->andReturnTrue();

        $this->allowGates();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::on(function ($event) use ($user) {
                             return $event instanceof UserAuthenticated
                                    && $event->userId->id === $user->id->id
                                    && $event->context === OperatingContext::Account;
                         }))
                         ->once();

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    #[Test]
    public function executeSavesAuthTokenToRepository(): void
    {
        $user = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->authTokenRepository->shouldReceive('save')
                                  ->with(Mockery::on(function ($token) use ($user) {
                                      return $token instanceof AuthToken
                                             && $token->userId->id === $user->id->id
                                             && $token->context === OperatingContext::Account;
                                  }))
                                  ->once()
                                  ->andReturnTrue();

        $this->allowGates();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(UserAuthenticated::class))
                         ->once();

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    // ——————————————————————————————————————————————
    // execute — attempt gate
    // ——————————————————————————————————————————————

    #[Test]
    public function executeThrowsWhenAttemptGateIsCancelled(): void
    {
        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once()
                         ->andReturnUsing(function (AuthenticationAttempting $event) {
                             $event->cancel();
                         });

        $this->expectException(UnableToAuthenticate::class);

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    #[Test]
    public function executeThrowsWithCustomReasonWhenAttemptGateCancelled(): void
    {
        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once()
                         ->andReturnUsing(function (AuthenticationAttempting $event) {
                             $event->cancel('IP banned');
                         });

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('IP banned');

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    // ——————————————————————————————————————————————
    // execute — credentials
    // ——————————————————————————————————————————————

    #[Test]
    public function executeThrowsInvalidCredentialsWhenUserNotFound(): void
    {
        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturnNull();

        $this->expectException(InvalidCredentials::class);

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    #[Test]
    public function executeThrowsInvalidCredentialsWhenPasswordIsWrong(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->expectException(InvalidCredentials::class);

        $this->handler->execute(
            'test@example.com',
            'wrong-password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    // ——————————————————————————————————————————————
    // execute — user status
    // ——————————————————————————————————————————————

    #[Test]
    public function executeThrowsWhenUserIsInactive(): void
    {
        $user = $this->makeUser(active: false);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('User is inactive');

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    #[Test]
    public function executeThrowsWhenUserCannotOperateInContext(): void
    {
        $user = $this->makeUser(operatesIn: [OperatingContext::Platform]);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('User cannot operate in this context');

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    #[Test]
    public function executeThrowsWhenUserHasNoOperatingContexts(): void
    {
        $user = $this->makeUser(operatesIn: []);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('User cannot operate in this context');

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    // ——————————————————————————————————————————————
    // execute — authorisation gate
    // ——————————————————————————————————————————————

    #[Test]
    public function executeThrowsWhenAuthorisationGateIsCancelled(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAuthorising::class))
                         ->once()
                         ->andReturnUsing(function (AuthenticationAuthorising $event) {
                             $event->cancel('Account suspended');
                         });

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('Account suspended');

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }

    // ——————————————————————————————————————————————
    // execute — save failure
    // ——————————————————————————————————————————————

    #[Test]
    public function executeThrowsWhenAuthTokenSaveFails(): void
    {
        $user = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with('test@example.com')
                             ->once()
                             ->andReturn($user);

        $this->authTokenRepository->shouldReceive('save')
                                  ->with(Mockery::type(AuthToken::class))
                                  ->once()
                                  ->andReturnFalse();

        $this->allowGates();

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('Unable to save auth token');

        $this->handler->execute(
            'test@example.com',
            'password',
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
        );
    }
}
