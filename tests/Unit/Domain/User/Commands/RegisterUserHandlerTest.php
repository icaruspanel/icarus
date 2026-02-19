<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\User\Commands;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\Shared\RecordsEvents;
use Icarus\Domain\User\Commands\RegisterUser;
use Icarus\Domain\User\Commands\RegisterUserHandler;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('user')]
class RegisterUserHandlerTest extends TestCase
{
    private UserRepository&MockInterface $repository;

    private EventDispatcher&MockInterface $dispatcher;

    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UserRepository::class);
        $this->dispatcher = Mockery::mock(EventDispatcher::class);

        $this->handler = new RegisterUserHandler(
            $this->repository,
            $this->dispatcher,
        );
    }

    private function makeCommand(
        string $name = 'Test User',
        string $email = 'test@example.com',
        string $password = 'password',
        ?CarbonImmutable $verifiedAt = null,
    ): RegisterUser
    {
        return new RegisterUser($name, $email, $password, $verifiedAt);
    }

    #[Test]
    public function invokeReturnsRegisteredUser(): void
    {
        $command = $this->makeCommand();

        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        $user = ($this->handler)($command);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email->email);
        $this->assertTrue($user->password->verify('password'));
        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function invokeSavesUserToRepository(): void
    {
        $command = $this->makeCommand();

        $this->repository->shouldReceive('save')
                         ->with(Mockery::on(function ($user) {
                             return $user instanceof User
                                    && $user->name === 'Test User'
                                    && $user->email->email === 'test@example.com';
                         }))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        ($this->handler)($command);
    }

    #[Test]
    public function invokeDispatchesEventsFromUser(): void
    {
        $command = $this->makeCommand();

        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::on(function ($recorder) {
                             return $recorder instanceof User;
                         }))
                         ->once();

        ($this->handler)($command);
    }

    #[Test]
    public function invokeCreatesUnverifiedUserByDefault(): void
    {
        $command = $this->makeCommand();

        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        $user = ($this->handler)($command);

        $this->assertFalse($user->email->verified);
        $this->assertNull($user->email->verifiedAt);
    }

    #[Test]
    public function invokeCreatesVerifiedUserWhenVerifiedAtIsProvided(): void
    {
        $now     = CarbonImmutable::now();
        $command = $this->makeCommand(verifiedAt: $now);

        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        $user = ($this->handler)($command);

        $this->assertTrue($user->email->verified);
        $this->assertNotNull($user->email->verifiedAt);
        $this->assertTrue($user->email->verifiedAt->equalTo($now));
    }
}
