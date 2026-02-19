<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\Events\UserEmailChanged;
use Icarus\Domain\User\Events\UserPasswordChanged;
use Icarus\Domain\User\Events\UserRegistered;
use Icarus\Domain\User\HashedPassword;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserEmail;
use Icarus\Domain\User\UserId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('user')]
class UserTest extends TestCase
{
    // ——————————————————————————————————————————————
    // register
    // ——————————————————————————————————————————————

    #[Test]
    public function registerCreatesUserWithValidId(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');

        $this->assertTrue(Ulid::isValid($user->id->id));
    }

    #[Test]
    public function registerAssignsNameAndEmail(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email->email);
    }

    #[Test]
    public function registerHashesPassword(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');

        $this->assertTrue($user->password->verify('password'));
        $this->assertNotSame('password', $user->password->hash);
    }

    #[Test]
    public function registerDefaultsToActiveUser(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');

        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function registerCreatesUnverifiedEmailByDefault(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');

        $this->assertFalse($user->email->verified);
        $this->assertNull($user->email->verifiedAt);
    }

    #[Test]
    public function registerCreatesVerifiedEmailWhenVerifiedAtProvided(): void
    {
        $now  = CarbonImmutable::now();
        $user = User::register('Test User', 'test@example.com', 'password', $now);

        $this->assertTrue($user->email->verified);
        $this->assertNotNull($user->email->verifiedAt);
        $this->assertTrue($user->email->verifiedAt->equalTo($now));
    }

    #[Test]
    public function registerRecordsUserRegisteredEvent(): void
    {
        $user   = User::register('Test User', 'test@example.com', 'password');
        $events = $user->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
        $this->assertSame($user->id, $events[0]->userId);
        $this->assertSame('Test User', $events[0]->name);
        $this->assertSame('test@example.com', $events[0]->email);
    }

    // ——————————————————————————————————————————————
    // isActive
    // ——————————————————————————————————————————————

    #[Test]
    public function isActiveReturnsTrueWhenActive(): void
    {
        $user = new User(
            UserId::generate(),
            'Test User',
            UserEmail::unverified('test@example.com'),
            HashedPassword::from('password'),
            true,
        );

        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function isActiveReturnsFalseWhenInactive(): void
    {
        $user = new User(
            UserId::generate(),
            'Test User',
            UserEmail::unverified('test@example.com'),
            HashedPassword::from('password'),
            false,
        );

        $this->assertFalse($user->isActive());
    }

    // ——————————————————————————————————————————————
    // changeEmail
    // ——————————————————————————————————————————————

    #[Test]
    public function changeEmailUpdatesEmail(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');
        $user->releaseEvents();

        $user->changeEmail('new@example.com');

        $this->assertSame('new@example.com', $user->email->email);
    }

    #[Test]
    public function changeEmailSetsNewEmailAsUnverified(): void
    {
        $now  = CarbonImmutable::now();
        $user = User::register('Test User', 'test@example.com', 'password', $now);
        $user->releaseEvents();

        $this->assertTrue($user->email->verified);

        $user->changeEmail('new@example.com');

        $this->assertFalse($user->email->verified);
        $this->assertNull($user->email->verifiedAt);
    }

    #[Test]
    public function changeEmailRecordsUserEmailChangedEvent(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');
        $user->releaseEvents();

        $user->changeEmail('new@example.com');

        $events = $user->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserEmailChanged::class, $events[0]);
        $this->assertSame($user->id, $events[0]->userId);
        $this->assertSame('test@example.com', $events[0]->oldEmail);
        $this->assertSame('new@example.com', $events[0]->newEmail);
    }

    #[Test]
    public function changeEmailDoesNothingWhenEmailIsSame(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');
        $user->releaseEvents();

        $user->changeEmail('test@example.com');

        $events = $user->releaseEvents();

        $this->assertCount(0, $events);
        $this->assertSame('test@example.com', $user->email->email);
    }

    #[Test]
    public function changeEmailReturnsSelf(): void
    {
        $user   = User::register('Test User', 'test@example.com', 'password');
        $result = $user->changeEmail('new@example.com');

        $this->assertSame($user, $result);
    }

    // ——————————————————————————————————————————————
    // changePassword
    // ——————————————————————————————————————————————

    #[Test]
    public function changePasswordUpdatesPassword(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');
        $user->releaseEvents();

        $user->changePassword('new-password');

        $this->assertTrue($user->password->verify('new-password'));
        $this->assertFalse($user->password->verify('password'));
    }

    #[Test]
    public function changePasswordRecordsUserPasswordChangedEvent(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');
        $user->releaseEvents();

        $user->changePassword('new-password');

        $events = $user->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserPasswordChanged::class, $events[0]);
        $this->assertSame($user->id, $events[0]->userId);
    }

    #[Test]
    public function changePasswordDoesNothingWhenPasswordIsSame(): void
    {
        $user = User::register('Test User', 'test@example.com', 'password');
        $user->releaseEvents();

        $user->changePassword('password');

        $events = $user->releaseEvents();

        $this->assertCount(0, $events);
        $this->assertTrue($user->password->verify('password'));
    }

    #[Test]
    public function changePasswordReturnsSelf(): void
    {
        $user   = User::register('Test User', 'test@example.com', 'password');
        $result = $user->changePassword('new-password');

        $this->assertSame($user, $result);
    }
}
