<?php

namespace Icarus\Domain\Permission;

use Icarus\Domain\Shared\OperatingContext;
use Illuminate\Support\Collection;

/**
 *
 */
interface RoleRepository
{
    /**
     * Save the role.
     *
     * @param \Icarus\Domain\Permission\Role $role
     *
     * @return bool
     */
    public function save(Role $role): bool;

    /**
     * Find a role by its ID.
     *
     * @param \Icarus\Domain\Permission\RoleId $id
     *
     * @return \Icarus\Domain\Permission\Role|null
     */
    public function find(RoleId $id): ?Role;

    /**
     * Find roles by operating context.
     *
     * @param \Icarus\Domain\Shared\OperatingContext $context
     *
     * @return \Illuminate\Support\Collection<int, \Icarus\Domain\Permission\Role>
     */
    public function findByContext(OperatingContext $context): Collection;
}
