<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel;

use Icarus\Kernel\IdentityMap;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Stubs\ConcreteId;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('infrastructure'), Group('shared')]
class IdentityMapTest extends TestCase
{
    // ——————————————————————————————————————————————
    // has
    // ——————————————————————————————————————————————

    #[Test]
    public function hasReturnsFalseWhenEmpty(): void
    {
        $map = new IdentityMap();

        $this->assertFalse($map->has(ConcreteId::generate(), stdClass::class));
    }

    #[Test]
    public function hasReturnsTrueAfterPut(): void
    {
        $map    = new IdentityMap();
        $id     = ConcreteId::generate();
        $object = new stdClass();

        $map->put($id, $object);

        $this->assertTrue($map->has($id, stdClass::class));
    }

    #[Test]
    public function hasReturnsFalseForDifferentClass(): void
    {
        $map    = new IdentityMap();
        $id     = ConcreteId::generate();
        $object = new stdClass();

        $map->put($id, $object);

        $this->assertFalse($map->has($id, self::class));
    }

    #[Test]
    public function hasReturnsFalseForDifferentId(): void
    {
        $map    = new IdentityMap();
        $object = new stdClass();

        $map->put(ConcreteId::generate(), $object);

        $this->assertFalse($map->has(ConcreteId::generate(), stdClass::class));
    }

    // ——————————————————————————————————————————————
    // get
    // ——————————————————————————————————————————————

    #[Test]
    public function getReturnsNullWhenEmpty(): void
    {
        $map = new IdentityMap();

        $this->assertNull($map->get(ConcreteId::generate(), stdClass::class));
    }

    #[Test]
    public function getReturnsSameObjectAfterPut(): void
    {
        $map    = new IdentityMap();
        $id     = ConcreteId::generate();
        $object = new stdClass();

        $map->put($id, $object);

        $this->assertSame($object, $map->get($id, stdClass::class));
    }

    #[Test]
    public function getReturnsNullForDifferentClass(): void
    {
        $map    = new IdentityMap();
        $id     = ConcreteId::generate();
        $object = new stdClass();

        $map->put($id, $object);

        $this->assertNull($map->get($id, self::class));
    }

    // ——————————————————————————————————————————————
    // put
    // ——————————————————————————————————————————————

    #[Test]
    public function putOverwritesExistingObject(): void
    {
        $map     = new IdentityMap();
        $id      = ConcreteId::generate();
        $objectA = new stdClass();
        $objectB = new stdClass();

        $map->put($id, $objectA);
        $map->put($id, $objectB);

        $this->assertSame($objectB, $map->get($id, stdClass::class));
        $this->assertNotSame($objectA, $map->get($id, stdClass::class));
    }

    #[Test]
    public function putStoresMultipleObjectsWithDifferentIds(): void
    {
        $map     = new IdentityMap();
        $idA     = ConcreteId::generate();
        $idB     = ConcreteId::generate();
        $objectA = new stdClass();
        $objectB = new stdClass();

        $map->put($idA, $objectA);
        $map->put($idB, $objectB);

        $this->assertSame($objectA, $map->get($idA, stdClass::class));
        $this->assertSame($objectB, $map->get($idB, stdClass::class));
    }
}
