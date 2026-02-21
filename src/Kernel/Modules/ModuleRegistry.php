<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules;

use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Modules\Collectors\CollectorRegistry;
use RuntimeException;

final class ModuleRegistry
{
    /**
     * @var \Icarus\Kernel\Contracts\EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @var \Icarus\Kernel\Modules\Collectors\CollectorRegistry
     */
    private CollectorRegistry $collectorRegistry;

    /**
     * The list of modules registered in the application.
     *
     * @var array<string, \Icarus\Kernel\Modules\RegisteredModule>
     */
    private array $modules = [];

    public function __construct(
        EventDispatcher   $dispatcher,
        CollectorRegistry $collectorRegistry
    )
    {
        $this->dispatcher        = $dispatcher;
        $this->collectorRegistry = $collectorRegistry;
    }

    /**
     * Check if a module with the given identifier is already registered.
     *
     * @param string $ident
     *
     * @return bool
     */
    public function registered(string $ident): bool
    {
        return isset($this->modules[$ident]);
    }

    /**
     * Register a module in the application.
     *
     * @param RegisteredModule $module
     *
     * @return void
     */
    public function register(RegisteredModule $module): void
    {
        if ($this->registered($module->ident)) {
            /** @todo Replace exception with custom exception */
            throw new RuntimeException("Module with identifier {$module->ident} is already registered.");
        }

        $this->modules[$module->ident] = $module;
    }

    /**
     * @param class-string<\Icarus\Kernel\Modules\Contracts\Collector> $collector
     *
     * @return void
     */
    public function collect(string $collector): void
    {
        $handler = $this->collectorRegistry->get($collector);

        if ($handler === null) {
            return;
        }

        // TODO: Collect properly

        $handler->register();
    }
}
