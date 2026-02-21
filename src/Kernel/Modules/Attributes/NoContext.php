<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules\Attributes;

use Attribute;
use Icarus\Domain\Shared\OperatingContext;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class NoContext
{
}
