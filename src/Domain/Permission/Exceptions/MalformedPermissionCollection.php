<?php
declare(strict_types=1);

namespace Icarus\Domain\Permission\Exceptions;

use Icarus\Domain\Permission\RoleId;

final class MalformedPermissionCollection extends PermissionParsingFailure
{
    public static function make(?RoleId $roleId = null): self
    {
        return new self('Malformed permissions collection' . ($roleId ? " for role {$roleId}" : '.'));
    }
}
