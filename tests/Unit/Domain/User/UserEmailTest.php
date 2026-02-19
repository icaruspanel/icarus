<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\UserEmail;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('user')]
class UserEmailTest extends TestCase
{
    #[Test]
    public function unverifiedCreatesUnverifiedEmail(): void
    {
        $email = UserEmail::unverified('test@example.com');

        $this->assertSame('test@example.com', $email->email);
        $this->assertFalse($email->verified);
        $this->assertNull($email->verifiedAt);
    }

    #[Test]
    public function verifiedCreatesVerifiedEmail(): void
    {
        $now   = CarbonImmutable::now();
        $email = UserEmail::verified('test@example.com', $now);

        $this->assertSame('test@example.com', $email->email);
        $this->assertTrue($email->verified);
        $this->assertNotNull($email->verifiedAt);
        $this->assertTrue($email->verifiedAt->equalTo($now));
    }

    #[Test]
    public function createWithoutVerifiedAtReturnsUnverified(): void
    {
        $email = UserEmail::create('test@example.com');

        $this->assertSame('test@example.com', $email->email);
        $this->assertFalse($email->verified);
        $this->assertNull($email->verifiedAt);
    }

    #[Test]
    public function createWithVerifiedAtReturnsVerified(): void
    {
        $now   = CarbonImmutable::now();
        $email = UserEmail::create('test@example.com', $now);

        $this->assertSame('test@example.com', $email->email);
        $this->assertTrue($email->verified);
        $this->assertNotNull($email->verifiedAt);
        $this->assertTrue($email->verifiedAt->equalTo($now));
    }

    #[Test]
    public function constructorDefaultsToUnverified(): void
    {
        $email = new UserEmail('test@example.com');

        $this->assertSame('test@example.com', $email->email);
        $this->assertFalse($email->verified);
        $this->assertNull($email->verifiedAt);
    }
}
