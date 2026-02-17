<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Icarus\Domain\Shared\Id;

final class SnapshotMap
{
    /**
     * @var array<class-string, array<string, array<string, mixed>>>
     */
    private array $map = [];

    /**
     * Check if the snapshot map contains a snapshot for the given id and class.
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
     * Get the snapshot for the given id and class.
     *
     * @param \Icarus\Domain\Shared\Id $id
     * @param class-string             $class
     *
     * @return array<string, mixed>|null
     */
    public function get(Id $id, string $class): ?array
    {
        return $this->map[$class][$id->id] ?? null;
    }

    /**
     * Put a snapshot for the given id and class.
     *
     * @param \Icarus\Domain\Shared\Id $id
     * @param class-string             $class
     * @param array<string, mixed>     $snapshot
     *
     * @return void
     */
    public function put(Id $id, string $class, array $snapshot): void
    {
        $this->map[$class][$id->id] = $snapshot;
    }

    /**
     * @param \Icarus\Domain\Shared\Id $id
     * @param class-string             $class
     * @param array<string, mixed>     $current
     *
     * @return array<string, mixed>
     */
    public function toPersist(Id $id, string $class, array $current): array
    {
        $snapshot = $this->get($id, $class);

        if ($snapshot === null) {
            return $current;
        }

        return array_diff_assoc($current, $snapshot);
    }
}
