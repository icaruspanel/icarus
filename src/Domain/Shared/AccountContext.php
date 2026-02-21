<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

use Icarus\Domain\Account\AccountId;
use Icarus\Domain\Permission\PermissionGrants;

final readonly class AccountContext
{
    public function __construct(
        public AccountId $accountId,
        public PermissionGrants $permissions
    )
    {
    }
}
