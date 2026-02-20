<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserHydrator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('user')]
class UserHydratorTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private static array $dummyData;

    protected function setUp(): void
    {
        parent::setUp();

        self::$dummyData = [
            'id'          => Ulid::generate(),
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'password'    => '$argon2id$v=19$m=65536,t=4,p=1$dummyhash',
            'operates_in' => '["account","platform"]',
            'active'      => true,
            'verified_at' => '1988-06-24 02:05:07',
        ];
    }

    // ——————————————————————————————————————————————
    // hydrate
    // ——————————————————————————————————————————————

    #[Test]
    public function createsUserWithTheCorrectId(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['id'], $user->id->id);
    }

    #[Test]
    public function createsUserWithTheCorrectName(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['name'], $user->name);
    }

    #[Test]
    public function createsUserWithTheCorrectEmail(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['email'], $user->email->email);
    }

    #[Test]
    public function createsUserWithTheCorrectPassword(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertSame(self::$dummyData['password'], $user->password->hash);
    }

    #[Test]
    public function createsUserWithTheCorrectActiveState(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertTrue($user->active);
        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function createsInactiveUser(): void
    {
        $hydrator = new UserHydrator();

        $data           = self::$dummyData;
        $data['active'] = false;

        $user = $hydrator->hydrate($data);

        $this->assertFalse($user->active);
        $this->assertFalse($user->isActive());
    }

    #[Test]
    public function createsUserWithTheCorrectVerifiedAt(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertNotNull($user->email->verifiedAt);
        $this->assertTrue($user->email->verified);
        $this->assertSame(1988, $user->email->verifiedAt->year);
        $this->assertSame(6, $user->email->verifiedAt->month);
        $this->assertSame(24, $user->email->verifiedAt->day);
        $this->assertSame(2, $user->email->verifiedAt->hour);
        $this->assertSame(5, $user->email->verifiedAt->minute);
        $this->assertSame(7, $user->email->verifiedAt->second);
    }

    #[Test]
    public function createsUserWithTheCorrectOperatesIn(): void
    {
        $hydrator = new UserHydrator();

        $user = $hydrator->hydrate(self::$dummyData);

        $this->assertCount(2, $user->operatesIn);
        $this->assertSame(OperatingContext::Account, $user->operatesIn[0]);
        $this->assertSame(OperatingContext::Platform, $user->operatesIn[1]);
    }

    #[Test]
    public function createsUserWithEmptyOperatesIn(): void
    {
        $hydrator = new UserHydrator();

        $data                = self::$dummyData;
        $data['operates_in'] = '[]';

        $user = $hydrator->hydrate($data);

        $this->assertEmpty($user->operatesIn);
    }

    #[Test]
    public function createsUserWithNullVerifiedAt(): void
    {
        $hydrator = new UserHydrator();

        $data                = self::$dummyData;
        $data['verified_at'] = null;

        $user = $hydrator->hydrate($data);

        $this->assertNull($user->email->verifiedAt);
        $this->assertFalse($user->email->verified);
    }

    // ——————————————————————————————————————————————
    // dehydrate
    // ——————————————————————————————————————————————

    #[Test]
    public function dehydrateReturnsCorrectArrayKeys(): void
    {
        $hydrator = new UserHydrator();

        $user   = $hydrator->hydrate(self::$dummyData);
        $result = $hydrator->dehydrate($user);

        $expectedKeys = [
            'id', 'name', 'email', 'password', 'operates_in', 'active', 'verified_at',
        ];

        $this->assertSame($expectedKeys, array_keys($result));
    }

    #[Test]
    public function dehydrateFormatsVerifiedAtCorrectly(): void
    {
        $hydrator = new UserHydrator();

        $user   = $hydrator->hydrate(self::$dummyData);
        $result = $hydrator->dehydrate($user);

        $this->assertSame('1988-06-24 02:05:07', $result['verified_at']);
    }

    #[Test]
    public function dehydrateHandlesNullVerifiedAt(): void
    {
        $hydrator = new UserHydrator();

        $data                = self::$dummyData;
        $data['verified_at'] = null;

        $user   = $hydrator->hydrate($data);
        $result = $hydrator->dehydrate($user);

        $this->assertNull($result['verified_at']);
    }

    // ——————————————————————————————————————————————
    // round-trip
    // ——————————————————————————————————————————————

    #[Test]
    public function hydrateThenDehydrateReturnsOriginalData(): void
    {
        $hydrator = new UserHydrator();

        $user   = $hydrator->hydrate(self::$dummyData);
        $result = $hydrator->dehydrate($user);

        $this->assertSame(self::$dummyData, $result);
    }
}
