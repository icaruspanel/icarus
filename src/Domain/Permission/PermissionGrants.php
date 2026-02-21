<?php
declare(strict_types=1);

namespace Icarus\Domain\Permission;

use InvalidArgumentException;

final readonly class PermissionGrants
{
    /**
     * @var array<string, array<string>>
     */
    public array $permissions;

    /**
     * @param array<string, array<string>> $permissions
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Check if the permission grant contains the permission.
     *
     * @param string $namespace
     * @param string $permission
     *
     * @return bool
     */
    public function allows(string $namespace, string $permission): bool
    {
        if (str_contains($permission, '*')) {
            throw new InvalidArgumentException('Cannot check if permissions allow a wildcard permission.');
        }

        $availablePermissions = array_merge(
            $this->permissions[$namespace] ?? [],
            $this->permissions['*'] ?? []
        );

        if (empty($availablePermissions)) {
            return false;
        }

        return array_any(
            PermissionCollection::getValidPermissionsFor($permission),
            fn ($permissionToCheck) => in_array($permissionToCheck, $availablePermissions, true)
        );
    }
}
