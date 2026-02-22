<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules;

/**
 * @template TModule of object
 */
final readonly class ModuleManifest
{
    /**
     * @param string                                          $ident
     * @param string                                          $name
     * @param string|null                                     $description
     * @param class-string<TModule>                           $definition
     * @param array<\Icarus\Kernel\Modules\Capability>        $capabilities
     * @param array<string>                                   $dependencies
     * @param array<string>                                   $after
     * @param array<\Icarus\Kernel\Modules\RegistrationEntry> $registrations
     */
    public function __construct(
        public string  $ident,
        public string  $name,
        public ?string $description,
        public string  $definition,
        public array   $capabilities,
        public array   $dependencies,
        public array   $after,
        public array   $registrations
    )
    {
    }
}
