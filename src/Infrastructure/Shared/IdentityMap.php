<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Icarus\Domain\Shared\Id;

final class IdentityMap
{
    /**
     * @var array<class-string, array<string, object>>
     */
    private array $map = [];

    /**
     * Check if the identity map contains an object of the given class and id.
     *
     * @param \Icarus\Domain\Shared\Id $id
     * @param class-string             $class
     *
     * @return bool
     */
    public function has(Id $id, string $class): bool
    {
        return $this->get($id, $class) !== null;
    }

    /**
     * Get an object from the identity map.
     *
     * @template T of object
     *
     * @param \Icarus\Domain\Shared\Id $id
     * @param class-string             $class
     *
     * @phpstan-param class-string<T>  $class
     *
     * @return object|null
     *
     * @phpstan-return T|null
     */
    public function get(Id $id, string $class): ?object
    {
        /** @var T|null $object */
        $object = $this->map[$class][$id->id] ?? null;

        return $object;
    }

    /**
     * Put an object in the identity map.
     *
     * @param \Icarus\Domain\Shared\Id $id
     * @param object                   $object
     *
     * @return void
     */
    public function put(Id $id, object $object): void
    {
        $this->map[$object::class][$id->id] = $object;
    }
}
