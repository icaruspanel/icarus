<?php
declare(strict_types=1);

namespace Icarus\Kernel\Permission\Actions;

use Icarus\Domain\Account\AccountId;
use Icarus\Domain\Permission\PermissionGrants;
use Icarus\Domain\Permission\RoleHydrator;
use Icarus\Domain\Permission\RoleId;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use Icarus\Kernel\Concerns\HandlesIlluminateConnections;
use Icarus\Kernel\Persistence\Scopes\ScopedByContext;
use Illuminate\Database\ConnectionInterface;
use stdClass;

final readonly class GetPermissionGrantsForUser
{
    use HandlesIlluminateConnections;

    public function __construct(ConnectionInterface $connection)
    {
        $this->setConnection($connection);
    }

    /**
     * @param \Icarus\Domain\User\UserId             $userId
     * @param \Icarus\Domain\Shared\OperatingContext $context
     * @param \Icarus\Domain\Account\AccountId|null  $accountId
     *
     * @return \Icarus\Domain\Permission\PermissionGrants|null
     * @throws \JsonException
     */
    public function execute(
        UserId           $userId,
        OperatingContext $context,
        ?AccountId       $accountId = null
    ): ?PermissionGrants
    {
        $permissions = [];

        $results = $this->query()
                        ->select(['roles.id', 'roles.permissions'])
                        ->from('roles')
                        ->join('role_users', 'roles.id', '=', 'role_users.role_id')
                        ->where('role_users.user_id', '=', $userId->id)
                        ->tap(new ScopedByContext($context, 'roles'))
                        ->when($accountId !== null, function ($query) use ($accountId) {
                            /** @var AccountId $accountId */
                            $query->where('role_users.account_id', '=', $accountId->id);
                        })
                        ->get();

        if ($results->isNotEmpty()) {
            $results->each(function (stdClass $row) use (&$permissions) {
                $data = (array)$row;

                /** @var array{id: string, permissions: string} $data */

                $extracted = RoleHydrator::extractPermissions($data['permissions'], new RoleId($data['id']));

                foreach ($extracted as $namespace => $extractedPermissions) {
                    if (isset($permissions[$namespace])) {
                        $permissions[$namespace] = array_merge($permissions[$namespace], $extractedPermissions);
                    } else {
                        $permissions[$namespace] = $extractedPermissions;
                    }
                }
            });

            return new PermissionGrants($permissions);
        }

        return null;
    }
}
