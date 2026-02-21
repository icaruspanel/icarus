<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel\User\Actions;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\RecordsEvents;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\User\Actions\RegisterUser;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('kernel'), Group('user')]
class RegisterUserTest extends TestCase
{
    private UserRepository&MockInterface $repository;

    private EventDispatcher&MockInterface $dispatcher;

    private RegisterUser $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UserRepository::class);
        $this->dispatcher = Mockery::mock(EventDispatcher::class);

        $this->handler = new RegisterUser(
            $this->repository,
            $this->dispatcher,
        );
    }

    #[Test]
    public function invokeReturnsRegisteredUser(): void
    {
        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        $user = ($this->handler)('Test User', 'test@example.com', 'password');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email->email);
        $this->assertTrue($user->password->verify('password'));
        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function invokeSavesUserToRepository(): void
    {
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

        ($this->handler)('Test User', 'test@example.com', 'password');
    }

    #[Test]
    public function invokeDispatchesEventsFromUser(): void
    {
        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::on(function ($recorder) {
                             return $recorder instanceof User;
                         }))
                         ->once();

        ($this->handler)('Test User', 'test@example.com', 'password');
    }

    #[Test]
    public function invokeCreatesUnverifiedUserByDefault(): void
    {
        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        $user = ($this->handler)('Test User', 'test@example.com', 'password');

        $this->assertFalse($user->email->verified);
        $this->assertNull($user->email->verifiedAt);
    }

    #[Test]
    public function invokeCreatesVerifiedUserWhenVerifiedAtIsProvided(): void
    {
        $now = CarbonImmutable::now();

        $this->repository->shouldReceive('save')
                         ->with(Mockery::type(User::class))
                         ->once();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with(Mockery::type(RecordsEvents::class))
                         ->once();

        $user = ($this->handler)('Test User', 'test@example.com', 'password', $now);

        $this->assertTrue($user->email->verified);
        $this->assertNotNull($user->email->verifiedAt);
        $this->assertTrue($user->email->verifiedAt->equalTo($now));
    }
}
