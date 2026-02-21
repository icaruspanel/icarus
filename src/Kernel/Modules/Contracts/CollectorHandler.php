<?php

namespace Icarus\Kernel\Modules\Contracts;

use Icarus\Kernel\Modules\Attributes\Module;
use Icarus\Kernel\Modules\Attributes\Register;

/**
 * @template TCollector of \Icarus\Kernel\Modules\Contracts\Collector
 */
interface CollectorHandler
{
    /**
     * The class name of the collector handled by this handler.
     *
     * @return class-string<\Icarus\Kernel\Modules\Contracts\Collector>
     *
     * @phpstan-return class-string<TCollector>
     */
    public function collects(): string;

    /**
     * Create a collector instance for the given module.
     *
     * @param \Icarus\Kernel\Modules\Attributes\Module   $module
     * @param \Icarus\Kernel\Modules\Attributes\Register $register
     *
     * @return \Icarus\Kernel\Modules\Contracts\Collector
     *
     * @phpstan-return Collector
     */
    public function create(Module $module, Register $register): Collector;

    /**
     * Register the collected data.
     *
     * @return void
     */
    public function register(): void;
}
