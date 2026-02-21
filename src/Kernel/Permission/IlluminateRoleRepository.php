<?php
declare(strict_types=1);

namespace Icarus\Kernel\Permission;

use Icarus\Domain\Permission\Role;
use Icarus\Domain\Permission\RoleHydrator;
use Icarus\Domain\Permission\RoleId;
use Icarus\Domain\Permission\RoleRepository;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Contracts\RecordsLifecycleEvents;
use Icarus\Kernel\IdentityMap;
use Icarus\Kernel\Persistence\IlluminateBaseRepository;
use Icarus\Kernel\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use stdClass;

/**
 * @phpstan-import-type RoleData from \Icarus\Domain\Permission\RoleHydrator
 *
 * @extends \Icarus\Kernel\Persistence\IlluminateBaseRepository<\Icarus\Domain\Permission\Role>
 */
final class IlluminateRoleRepository extends IlluminateBaseRepository implements RoleRepository, RecordsLifecycleEvents
{
    public const string TABLE = 'roles';

    public const array FIELDS = ['id', 'context', 'name', 'description', 'permissions'];

    /**
     * @var \Icarus\Domain\Permission\RoleHydrator
     */
    private RoleHydrator $hydrator;

    public function __construct(
        RoleHydrator        $hydrator,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        EventDispatcher     $dispatcher
    )
    {
        $this->hydrator = $hydrator;

        parent::__construct(
            Role::class,
            $connection,
            $identityMap,
            $snapshotMap,
            $dispatcher
        );
    }

    /**
     * @param array<string, mixed>|\stdClass $results
     *
     * @phpstan-param RoleData|stdClass      $results
     *
     * @return \Icarus\Domain\Permission\Role
     *
     * @throws \JsonException
     */
    protected function hydrate(array|stdClass $results): Role
    {
        $results = (array)$results;

        /**
         * @var RoleData $results
         */

        // Hydrate the role object.
        $role = $this->hydrator->hydrate($results);

        // Make sure a snapshot is stored too.
        $this->storeSnapshot($role->id, $results);

        // And store its store in the identity map.
        $this->storeIdentity($role->id, $role);

        return $role;
    }

    /**
     * @param object                                 $aggregate
     *
     * @phpstan-param \Icarus\Domain\Permission\Role $aggregate
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    protected function dehydrate(object $aggregate): array
    {
        return $this->hydrator->dehydrate($aggregate);
    }

    /**
     * Save the role.
     *
     * @param \Icarus\Domain\Permission\Role $role
     *
     * @return bool
     *
     * @throws \JsonException
     */
    public function save(Role $role): bool
    {
        return $this->shouldCreate($role->id)
            ? $this->create($role->id, $role, self::TABLE)
            : $this->update($role->id, $role, self::TABLE);
    }

    /**
     * Find a role by its ID.
     *
     * @param \Icarus\Domain\Permission\RoleId $id
     *
     * @return \Icarus\Domain\Permission\Role|null
     *
     * @throws \JsonException
     */
    public function find(RoleId $id): ?Role
    {
        // Short-circuit and use the object from the identity map if it exists.
        if ($this->identityMap->has($id, Role::class)) {
            return $this->identityMap->get($id, Role::class);
        }

        $results = $this->query()
                        ->select(self::FIELDS)
                        ->where('id', $id)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        return $this->hydrate($results);
    }

    /**
     * Find roles by operating context.
     *
     * @param \Icarus\Domain\Shared\OperatingContext $context
     *
     * @return \Illuminate\Support\Collection<int, \Icarus\Domain\Permission\Role>
     */
    public function findByContext(OperatingContext $context): Collection
    {
        /** @var Collection<int, stdClass> $results */
        $results = $this->query()
                        ->select(self::FIELDS)
                        ->where('context', $context->value)
                        ->from(self::TABLE)
                        ->get();

        /** @var Collection<int, Role> $roles */
        $roles = $results->map(function (stdClass $row) {
            $data = (array)$row;

            /** @var RoleData $data */

            $id = new RoleId($data['id']);

            // Short-circuit and use the object from the identity map if it exists.
            if ($this->identityMap->has($id, Role::class)) {
                return $this->identityMap->get($id, Role::class);
            }

            return $this->hydrate($data);
        });

        return $roles;
    }
}
