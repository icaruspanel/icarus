<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules\Exceptions;

use Icarus\Kernel\Modules\Attributes\Module;
use Icarus\Kernel\Modules\Capability;
use LogicException;

final class MissingCapability extends LogicException
{
    public static function make(Module $module, Capability $capability): self
    {
        return new self(sprintf(
            'Module %s does not have the %s capability',
            $module->ident, $capability->value
        ));
    }
}
