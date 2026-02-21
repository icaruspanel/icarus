<?php
declare(strict_types=1);

namespace Icarus\Domain\Permission;

use Icarus\Domain\Shared\HasEvents;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\Shared\RecordsEvents;

final class Role implements RecordsEvents
{
    use HasEvents;

    public readonly RoleId $id;

    private(set) OperatingContext $context;

    private(set) string $name;

    private(set) ?string $description = null;

    private(set) PermissionCollection $permissions;

    public function __construct(
        RoleId               $id,
        OperatingContext     $context,
        string               $name,
        ?string              $description,
        PermissionCollection $permissions
    )
    {
        $this->id          = $id;
        $this->context     = $context;
        $this->name        = $name;
        $this->description = $description;
        $this->permissions = $permissions;
    }
}
