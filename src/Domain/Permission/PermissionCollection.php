<?php

namespace Icarus\Domain\Permission;

use InvalidArgumentException;
use JsonSerializable;

final class PermissionCollection implements JsonSerializable
{
    /**
     * @param string $permission
     *
     * @return array<string>
     */
    public static function getValidPermissionsFor(string $permission): array
    {
        $availablePermissions = ['*'];

        if ($permission !== '*') {
            $availablePermissions[] = $permission;

            $components = explode('.', $permission);

            for ($i = 0; $i < count($components) - 1; $i++) {
                $availablePermissions[] = implode('.', array_slice($components, 0, $i + 1)) . '.*';
            }
        }

        return $availablePermissions;
    }

    /**
     * @var array<string, array<string>>
     */
    private array $permissions;

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
            self::getValidPermissionsFor($permission),
            fn ($permissionToCheck) => in_array($permissionToCheck, $availablePermissions, true)
        );
    }

    /**
     * Check if the permission grant contains the permission.
     *
     * @param string $namespace
     * @param string $permission
     *
     * @return bool
     */
    public function has(string $namespace, string $permission): bool
    {
        return in_array($permission, $this->permissions[$namespace] ?? [], true);
    }

    /**
     * Add a permission to the grant.
     *
     * @param string $namespace
     * @param string $permission
     *
     * @return bool
     */
    public function add(string $namespace, string $permission): bool
    {
        $this->permissions[$namespace][] = $permission;
        $this->permissions[$namespace]   = array_unique($this->permissions[$namespace]);

        return true;
    }

    /**
     * Remove a permission from the grant.
     *
     * @param string $namespace
     * @param string $permission
     *
     * @return bool
     */
    public function remove(string $namespace, string $permission): bool
    {
        $index = array_search($permission, $this->permissions[$namespace] ?? [], true);

        if ($index === false) {
            return false;
        }

        unset($this->permissions[$namespace][$index]);

        return true;
    }

    /**
     * Flatten the permissions into a JSON serializable array.
     *
     * @return array<string>
     */
    public function jsonSerialize(): array
    {
        $permissions = [];

        foreach ($this->permissions as $namespace => $permissionsForNamespace) {
            foreach ($permissionsForNamespace as $permission) {
                $permissions[] = $namespace . ':' . $permission;
            }
        }

        return $permissions;
    }
}
