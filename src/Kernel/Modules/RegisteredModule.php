<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules;

/**
 * @template TModule of object
 */
final readonly class RegisteredModule
{
    public readonly string $ident;

    public readonly string $name;

    public readonly ?string $description;

    /**
     * @var object
     * @phpstan-var TModule
     */
    public readonly object $definition;

    /**
     * @var array<\Icarus\Kernel\Modules\Capability>
     */
    public readonly array $capabilities;

    /**
     * @param string                                   $ident
     * @param string                                   $name
     * @param string|null                              $description
     * @param object                                   $definition
     * @param array<\Icarus\Kernel\Modules\Capability> $capabilities
     *
     * @phpstan-param TModule                          $definition
     */
    public function __construct(
        string  $ident,
        string  $name,
        ?string $description,
        object  $definition,
        array   $capabilities
    )
    {
        $this->ident        = $ident;
        $this->name         = $name;
        $this->description  = $description;
        $this->definition   = $definition;
        $this->capabilities = $capabilities;
    }
}
