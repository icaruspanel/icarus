<?php
declare(strict_types=1);

namespace Icarus\Kernel\Persistence;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\Id;
use Icarus\Domain\Shared\RecordsEvents;
use Icarus\Kernel\Concerns\HandlesIlluminateConnections;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Contracts\RecordsLifecycleEvents;
use Icarus\Kernel\IdentityMap;
use Icarus\Kernel\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use stdClass;

/**
 * @template TAggregateRoot of object
 */
abstract class IlluminateBaseRepository
{
    use HandlesIlluminateConnections;

    /**
     * @var class-string<TAggregateRoot>
     */
    private string $aggregateClass;

    /**
     * @var \Icarus\Kernel\IdentityMap
     */
    private(set) protected IdentityMap $identityMap;

    /**
     * @var \Icarus\Kernel\SnapshotMap
     */
    private(set) protected SnapshotMap $snapshotMap;

    /**
     * @var \Icarus\Kernel\Contracts\EventDispatcher
     */
    private(set) protected EventDispatcher $dispatcher;

    /**
     * @param class-string<TAggregateRoot>             $aggregateClass
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Icarus\Kernel\IdentityMap               $identityMap
     * @param \Icarus\Kernel\SnapshotMap               $snapshotMap
     * @param \Icarus\Kernel\Contracts\EventDispatcher $dispatcher
     */
    public function __construct(
        string              $aggregateClass,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        EventDispatcher     $dispatcher
    )
    {
        $this->aggregateClass = $aggregateClass;
        $this->identityMap    = $identityMap;
        $this->snapshotMap    = $snapshotMap;
        $this->dispatcher     = $dispatcher;

        $this->setConnection($connection);
    }

    /**
     * @param array<string, mixed>|\stdClass $results
     *
     * @return object
     *
     * @phpstan-return TAggregateRoot
     */
    abstract protected function hydrate(array|stdClass $results): object;

    /**
     * @param object                 $aggregate
     *
     * @phpstan-param TAggregateRoot $aggregate
     *
     * @return array<string, mixed>
     */
    abstract protected function dehydrate(object $aggregate): array;

    protected function shouldCreate(Id $id): bool
    {
        return $this->snapshotMap->has($id, $this->aggregateClass) === false;
    }

    /**
     * @param \Icarus\Domain\Shared\Id $id
     * @param object                   $aggregate
     * @param string                   $table
     *
     * @phpstan-param TAggregateRoot   $aggregate
     *
     * @return bool
     */
    protected function create(Id $id, object $aggregate, string $table): bool
    {
        $fields   = $this->dehydrate($aggregate);
        $toInsert = $fields;

        if ($this instanceof RecordsLifecycleEvents) {
            $toInsert['created_at'] = $toInsert['updated_at'] = CarbonImmutable::now();
        }

        $success = $this->query()
                        ->from($table)
                        ->insert($toInsert);

        // If it was successful, we have things to do.
        if ($success) {
            $this->handlePostPersist($id, $aggregate, $fields, true);

            return true;
        }

        return false;
    }

    /**
     * @param \Icarus\Domain\Shared\Id $id
     * @param object                   $aggregate
     * @param string                   $table
     * @param string                   $key
     *
     * @phpstan-param TAggregateRoot   $aggregate
     *
     * @return bool
     */
    protected function update(Id $id, object $aggregate, string $table, string $key = 'id'): bool
    {
        $fields   = $this->dehydrate($aggregate);
        $toUpdate = $this->snapshotMap->toPersist($id, $this->aggregateClass, $fields);

        if (empty($toUpdate)) {
            return true;
        }

        if ($this instanceof RecordsLifecycleEvents) {
            $toUpdate['updated_at'] = CarbonImmutable::now();
        }

        $success = $this->query()
                        ->from($table)
                        ->where($key, '=', $id)
                        ->update($toUpdate);

        // If it was successful, we have things to do.
        if ($success) {
            $this->handlePostPersist($id, $aggregate, $fields, false);

            return true;
        }

        return false;
    }

    /**
     * @param \Icarus\Domain\Shared\Id $id
     * @param object                   $aggregate
     * @param bool                     $force
     *
     * @phpstan-param TAggregateRoot   $aggregate
     *
     * @return void
     */
    protected function storeIdentity(Id $id, object $aggregate, bool $force = true): void
    {
        if ($force || ! $this->identityMap->has($id, $this->aggregateClass)) {
            $this->identityMap->put($id, $aggregate);
        }
    }

    /**
     * @param \Icarus\Domain\Shared\Id $id
     * @param array<string, mixed>     $fields
     *
     * @return void
     */
    protected function storeSnapshot(Id $id, array $fields): void
    {
        $this->snapshotMap->put($id, $this->aggregateClass, $fields);
    }

    /**
     * @param \Icarus\Domain\Shared\Id $id
     * @param object                   $aggregate
     * @param array<string, mixed>     $fields
     *
     * @phpstan-param TAggregateRoot   $aggregate
     *
     * @return void
     */
    protected function handlePostPersist(Id $id, object $aggregate, array $fields, bool $storeIdentity): void
    {
        if ($storeIdentity) {
            $this->storeIdentity($id, $aggregate);
        }

        $this->storeSnapshot($id, $fields);

        // And dispatch any necessary events.
        if ($aggregate instanceof RecordsEvents) {
            $this->dispatcher->dispatchFrom($aggregate);
        }
    }
}
