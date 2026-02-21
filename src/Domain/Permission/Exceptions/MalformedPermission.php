<?php
declare(strict_types=1);

namespace Icarus\Domain\Permission\Exceptions;

final class MalformedPermission extends PermissionParsingFailure
{
    public static function make(string $permission): self
    {
        return new self("Malformed permission: {$permission}");
    }
}
