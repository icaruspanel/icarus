<?php
declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Shared;

use Icarus\Infrastructure\Shared\SnapshotMap;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Stubs\ConcreteId;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('infrastructure'), Group('shared')]
class SnapshotMapTest extends TestCase
{
    // ——————————————————————————————————————————————
    // has
    // ——————————————————————————————————————————————

    #[Test]
    public function hasReturnsFalseWhenEmpty(): void
    {
        $map = new SnapshotMap();

        $this->assertFalse($map->has(ConcreteId::generate(), stdClass::class));
    }

    #[Test]
    public function hasReturnsTrueAfterPut(): void
    {
        $map = new SnapshotMap();
        $id  = ConcreteId::generate();

        $map->put($id, stdClass::class, ['name' => 'Test']);

        $this->assertTrue($map->has($id, stdClass::class));
    }

    #[Test]
    public function hasReturnsFalseForDifferentClass(): void
    {
        $map = new SnapshotMap();
        $id  = ConcreteId::generate();

        $map->put($id, stdClass::class, ['name' => 'Test']);

        $this->assertFalse($map->has($id, self::class));
    }

    #[Test]
    public function hasReturnsFalseForDifferentId(): void
    {
        $map = new SnapshotMap();

        $map->put(ConcreteId::generate(), stdClass::class, ['name' => 'Test']);

        $this->assertFalse($map->has(ConcreteId::generate(), stdClass::class));
    }

    // ——————————————————————————————————————————————
    // get
    // ——————————————————————————————————————————————

    #[Test]
    public function getReturnsNullWhenEmpty(): void
    {
        $map = new SnapshotMap();

        $this->assertNull($map->get(ConcreteId::generate(), stdClass::class));
    }

    #[Test]
    public function getReturnsSnapshotAfterPut(): void
    {
        $map      = new SnapshotMap();
        $id       = ConcreteId::generate();
        $snapshot = ['name' => 'Test', 'email' => 'test@example.com'];

        $map->put($id, stdClass::class, $snapshot);

        $this->assertSame($snapshot, $map->get($id, stdClass::class));
    }

    #[Test]
    public function getReturnsNullForDifferentClass(): void
    {
        $map = new SnapshotMap();
        $id  = ConcreteId::generate();

        $map->put($id, stdClass::class, ['name' => 'Test']);

        $this->assertNull($map->get($id, self::class));
    }

    // ——————————————————————————————————————————————
    // put
    // ——————————————————————————————————————————————

    #[Test]
    public function putOverwritesExistingSnapshot(): void
    {
        $map       = new SnapshotMap();
        $id        = ConcreteId::generate();
        $snapshotA = ['name' => 'First'];
        $snapshotB = ['name' => 'Second'];

        $map->put($id, stdClass::class, $snapshotA);
        $map->put($id, stdClass::class, $snapshotB);

        $this->assertSame($snapshotB, $map->get($id, stdClass::class));
    }

    #[Test]
    public function putStoresMultipleSnapshotsWithDifferentIds(): void
    {
        $map       = new SnapshotMap();
        $idA       = ConcreteId::generate();
        $idB       = ConcreteId::generate();
        $snapshotA = ['name' => 'First'];
        $snapshotB = ['name' => 'Second'];

        $map->put($idA, stdClass::class, $snapshotA);
        $map->put($idB, stdClass::class, $snapshotB);

        $this->assertSame($snapshotA, $map->get($idA, stdClass::class));
        $this->assertSame($snapshotB, $map->get($idB, stdClass::class));
    }

    // ——————————————————————————————————————————————
    // toPersist
    // ——————————————————————————————————————————————

    #[Test]
    public function toPersistReturnsFullArrayWhenNoSnapshotExists(): void
    {
        $map     = new SnapshotMap();
        $id      = ConcreteId::generate();
        $current = ['name' => 'Test', 'email' => 'test@example.com'];

        $result = $map->toPersist($id, stdClass::class, $current);

        $this->assertSame($current, $result);
    }

    #[Test]
    public function toPersistReturnsOnlyChangedFields(): void
    {
        $map = new SnapshotMap();
        $id  = ConcreteId::generate();

        $map->put($id, stdClass::class, ['name' => 'Old', 'email' => 'test@example.com']);

        $current = ['name' => 'New', 'email' => 'test@example.com'];
        $result  = $map->toPersist($id, stdClass::class, $current);

        $this->assertSame(['name' => 'New'], $result);
    }

    #[Test]
    public function toPersistReturnsEmptyArrayWhenNothingChanged(): void
    {
        $map      = new SnapshotMap();
        $id       = ConcreteId::generate();
        $snapshot = ['name' => 'Test', 'email' => 'test@example.com'];

        $map->put($id, stdClass::class, $snapshot);

        $result = $map->toPersist($id, stdClass::class, $snapshot);

        $this->assertSame([], $result);
    }

    #[Test]
    public function toPersistReturnsAllFieldsWhenEverythingChanged(): void
    {
        $map = new SnapshotMap();
        $id  = ConcreteId::generate();

        $map->put($id, stdClass::class, ['name' => 'Old', 'email' => 'old@example.com']);

        $current = ['name' => 'New', 'email' => 'new@example.com'];
        $result  = $map->toPersist($id, stdClass::class, $current);

        $this->assertSame($current, $result);
    }
}
