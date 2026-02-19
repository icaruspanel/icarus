<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use AssertionError;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Ulid;
use Tests\Stubs\ConcreteId;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('shared')]
class IdTest extends TestCase
{
    #[Test]
    public function generateCreatesValidUlid(): void
    {
        $id = ConcreteId::generate();

        $this->assertTrue(Ulid::isValid($id->id));
    }

    #[Test]
    public function generateCreatesUniqueIds(): void
    {
        $idA = ConcreteId::generate();
        $idB = ConcreteId::generate();

        $this->assertNotSame($idA->id, $idB->id);
    }

    #[Test]
    public function constructorAcceptsValidUlid(): void
    {
        $ulid = Ulid::generate();
        $id   = new ConcreteId($ulid);

        $this->assertSame($ulid, $id->id);
    }

    #[Test]
    public function constructorRejectsInvalidUlid(): void
    {
        $this->expectException(AssertionError::class);

        new ConcreteId('not-a-valid-ulid');
    }

    #[Test]
    public function toStringReturnsTheUlid(): void
    {
        $ulid = Ulid::generate();
        $id   = new ConcreteId($ulid);

        $this->assertSame($ulid, (string) $id);
    }

    #[Test]
    public function generateReturnsInstanceOfConcreteClass(): void
    {
        $id = ConcreteId::generate();

        $this->assertInstanceOf(ConcreteId::class, $id);
    }
}
