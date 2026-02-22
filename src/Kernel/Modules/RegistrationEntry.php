<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules;

use Icarus\Domain\Shared\OperatingContext;

final readonly class RegistrationEntry
{
    /**
     * @param string                                                   $method
     * @param class-string<\Icarus\Kernel\Modules\Contracts\Collector> $collector
     * @param \Icarus\Domain\Shared\OperatingContext|null              $operatingContext
     * @param bool                                                     $noContext
     */
    public function __construct(
        public string            $method,
        public string            $collector,
        public ?OperatingContext $operatingContext,
        public bool              $noContext
    )
    {
    }
}
