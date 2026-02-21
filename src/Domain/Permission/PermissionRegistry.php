<?php
declare(strict_types=1);

namespace Icarus\Domain\Permission;

final readonly class PermissionRegistry
{
    /**
     * @var array<string, array<string>>
     */
    private(set) array $permissions;

    /**
     * @param array<string, array<string>> $permissions
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Get permissions for a specific module.
     *
     * @param string $module
     *
     * @return array<string>
     */
    public function from(string $module): array
    {
        return $this->permissions[$module] ?? [];
    }

    /**
     * Check if a permission exists.
     *
     * @param string $module
     * @param string $permission
     *
     * @return bool
     */
    public function has(string $module, string $permission): bool
    {
        return in_array($permission, $this->from($module));
    }
}
