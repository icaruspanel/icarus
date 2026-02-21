<?php
declare(strict_types=1);

namespace Icarus\Domain\Permission;

use Icarus\Domain\Permission\Exceptions\MalformedPermission;
use Icarus\Domain\Permission\Exceptions\MalformedPermissionCollection;
use Icarus\Domain\Shared\OperatingContext;

/**
 * @phpstan-type RoleData array{
 *     id: string,
 *     context: string,
 *     name: string,
 *     description: string|null,
 *     permissions: string
 * }
 */
final readonly class RoleHydrator
{
    /**
     * Hydrate a role from its raw data.
     *
     * @param array            $data
     *
     * @phpstan-param RoleData $data
     *
     * @return \Icarus\Domain\Permission\Role
     *
     * @throws \JsonException
     */
    public function hydrate(array $data): Role
    {
        return new Role(
            $roleId = new RoleId($data['id']),
            OperatingContext::from($data['context']),
            $data['name'],
            $data['description'] ?? null,
            new PermissionCollection(self::extractPermissions($data['permissions'], $roleId)),
        );
    }

    /**
     * Extract the permissions from the raw JSON string.
     *
     * @param string                                $permissions
     * @param \Icarus\Domain\Permission\RoleId|null $roleId
     *
     * @return array<string, array<string>>
     *
     * @throws \JsonException
     */
    public static function extractPermissions(string $permissions, ?RoleId $roleId = null): array
    {
        $extracted      = [];
        $rawPermissions = json_decode($permissions, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($rawPermissions)) {
            throw MalformedPermissionCollection::make($roleId);
        }

        foreach ($rawPermissions as $permission) {
            if ($permission === '*') {
                $extracted['*'] = ['*'];
            } else if (! is_string($permission)) {
                throw MalformedPermissionCollection::make($roleId);
            } else if (substr_count($permission, ':') !== 1) {
                throw MalformedPermission::make($permission);
            } else {
                [$namespace, $permission] = explode(':', $permission, 2);
                $extracted[$namespace][] = $permission;
            }
        }

        return $extracted;
    }

    /**
     * Dehydrate a role into its raw data.
     *
     * @param \Icarus\Domain\Permission\Role $role
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    public function dehydrate(Role $role): array
    {
        return [
            'id'          => $role->id->id,
            'context'     => $role->context->value,
            'name'        => $role->name,
            'description' => $role->description,
            'permissions' => json_encode($role->permissions->jsonSerialize(), JSON_THROW_ON_ERROR),
        ];
    }
}
