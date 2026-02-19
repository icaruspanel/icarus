<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Icarus\Domain\User\HashedPassword;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('user')]
class HashedPasswordTest extends TestCase
{
    #[Test]
    public function fromCreatesValidArgon2idPassword(): void
    {
        $hashed = HashedPassword::from('password');

        $this->assertTrue(str_starts_with($hashed->hash, '$argon2id$'));
    }

    #[Test]
    public function verifyReturnsTrueForCorrectPassword(): void
    {
        $hashed = HashedPassword::from('password');

        $this->assertTrue($hashed->verify('password'));
    }

    #[Test]
    public function verifyReturnsFalseForInorrectPassword(): void
    {
        $hashed = HashedPassword::from('password');

        $this->assertFalse($hashed->verify('password222'));
    }

    #[Test]
    public function fromProducesDifferentHashesForTheSameInput(): void
    {
        $hash1 = HashedPassword::from('password')->hash;
        $hash2 = HashedPassword::from('password')->hash;
        $hash3 = HashedPassword::from('password')->hash;
        $hash4 = HashedPassword::from('password')->hash;

        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertNotEquals($hash1, $hash4);

        $this->assertNotEquals($hash2, $hash3);
        $this->assertNotEquals($hash2, $hash4);

        $this->assertNotEquals($hash3, $hash4);
    }
}
