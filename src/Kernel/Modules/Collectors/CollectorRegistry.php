<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules\Collectors;

use Icarus\Kernel\Modules\Contracts\CollectorHandler;

final class CollectorRegistry
{
    /**
     * @var array<class-string<\Icarus\Kernel\Modules\Contracts\Collector>, \Icarus\Kernel\Modules\Contracts\CollectorHandler<*>>
     */
    private array $collectors = [];

    /**
     * @template TCollector of \Icarus\Kernel\Modules\Contracts\Collector
     *
     * @param \Icarus\Kernel\Modules\Contracts\CollectorHandler<TCollector> $handler
     *
     * @return \Icarus\Kernel\Modules\Collectors\CollectorRegistry
     */
    public function register(CollectorHandler $handler): self
    {
        $this->collectors[$handler->collects()] = $handler;

        return $this;
    }

    /**
     * @template TCollector of \Icarus\Kernel\Modules\Contracts\Collector
     *
     * @param class-string<TCollector> $collector
     *
     * @return \Icarus\Kernel\Modules\Contracts\CollectorHandler<TCollector>|null
     */
    public function get(string $collector): ?CollectorHandler
    {
        /** @var \Icarus\Kernel\Modules\Contracts\CollectorHandler<TCollector>|null $handler */
        $handler = $this->collectors[$collector] ?? null;

        return $handler;
    }
}
