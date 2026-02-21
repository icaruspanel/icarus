<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Module
{
    public function __construct(
        public string $ident
    )
    {
    }
}
