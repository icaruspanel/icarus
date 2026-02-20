<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken\Commands;

use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\AuthToken\Commands\AuthenticateUser;
use Icarus\Domain\AuthToken\Commands\AuthenticateUserHandler;
use Icarus\Domain\AuthToken\Commands\AuthenticationResult;
use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\AuthToken\Events\UserAuthenticated;
use Icarus\Domain\AuthToken\Exceptions\InvalidCredentials;
use Icarus\Domain\AuthToken\Exceptions\UnableToAuthenticate;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAttempting;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAuthorising;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\HashedPassword;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserEmail;
use Icarus\Domain\User\UserId;
use Icarus\Domain\User\UserRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('auth-token')]
class AuthenticateUserHandlerTest extends TestCase
{
    private AuthTokenRepository&MockInterface $authTokenRepository;

    private UserRepository&MockInterface $userRepository;

    private EventDispatcher&MockInterface $dispatcher;

    private AuthenticateUserHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authTokenRepository = Mockery::mock(AuthTokenRepository::class);
        $this->userRepository      = Mockery::mock(UserRepository::class);
        $this->dispatcher          = Mockery::mock(EventDispatcher::class);

        $this->handler = new AuthenticateUserHandler(
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

    private function makeCommand(string $email = 'test@example.com', string $password = 'password'): AuthenticateUser
    {
        return new AuthenticateUser(
            $email,
            $password,
            OperatingContext::Account,
            new Device('TestAgent', '127.0.0.1'),
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

    #[Test]
    public function handleReturnsAuthenticationResultOnSuccess(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
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

        $result = $this->handler->handle($command);

        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertSame($user->id->id, $result->userId->id);
        $this->assertSame(OperatingContext::Account, $result->context);
        $this->assertNotEmpty($result->token);
    }

    #[Test]
    public function handleDispatchesUserAuthenticatedEvent(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
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

        $this->handler->handle($command);
    }

    #[Test]
    public function handleSavesAuthTokenToRepository(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
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

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenAttemptGateIsCancelled(): void
    {
        $command = $this->makeCommand();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once()
                         ->andReturnUsing(function (AuthenticationAttempting $event) {
                             $event->cancel();
                         });

        $this->expectException(UnableToAuthenticate::class);

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWithCustomReasonWhenAttemptGateCancelled(): void
    {
        $command = $this->makeCommand();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once()
                         ->andReturnUsing(function (AuthenticationAttempting $event) {
                             $event->cancel('IP banned');
                         });

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('IP banned');

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsInvalidCredentialsWhenUserNotFound(): void
    {
        $command = $this->makeCommand();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
                             ->once()
                             ->andReturnNull();

        $this->expectException(InvalidCredentials::class);

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsInvalidCredentialsWhenPasswordIsWrong(): void
    {
        $command = $this->makeCommand(password: 'wrong-password');
        $user    = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
                             ->once()
                             ->andReturn($user);

        $this->expectException(InvalidCredentials::class);

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenUserIsInactive(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser(active: false);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
                             ->once()
                             ->andReturn($user);

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('User is inactive');

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenUserCannotOperateInContext(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser(operatesIn: [OperatingContext::Platform]);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
                             ->once()
                             ->andReturn($user);

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('User cannot operate in this context');

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenUserHasNoOperatingContexts(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser(operatesIn: []);

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
                             ->once()
                             ->andReturn($user);

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('User cannot operate in this context');

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenAuthorisationGateIsCancelled(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatch')
                         ->with(Mockery::type(AuthenticationAttempting::class))
                         ->once();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
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

        $this->handler->handle($command);
    }

    #[Test]
    public function handleThrowsWhenAuthTokenSaveFails(): void
    {
        $command = $this->makeCommand();
        $user    = $this->makeUser();

        $this->userRepository->shouldReceive('findByEmail')
                             ->with($command->email)
                             ->once()
                             ->andReturn($user);

        $this->authTokenRepository->shouldReceive('save')
                                  ->with(Mockery::type(AuthToken::class))
                                  ->once()
                                  ->andReturnFalse();

        $this->allowGates();

        $this->expectException(UnableToAuthenticate::class);
        $this->expectExceptionMessage('Unable to save auth token');

        $this->handler->handle($command);
    }
}
