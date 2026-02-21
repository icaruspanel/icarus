<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules\Collectors\Routes;

use Icarus\Kernel\Modules\Attributes\Module;
use Icarus\Kernel\Modules\Attributes\Register;
use Icarus\Kernel\Modules\Contracts\Collector;
use Icarus\Kernel\Modules\Contracts\CollectorHandler;
use Icarus\Kernel\Modules\Contracts\RouteCollector;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Str;

/**
 * @implements \Icarus\Kernel\Modules\Contracts\CollectorHandler<\Icarus\Kernel\Modules\Contracts\RouteCollector>
 */
final class RouteCollectorHandler implements CollectorHandler
{
    /**
     * @var \Illuminate\Routing\Router
     */
    private Router $router;

    /**
     * @var array<\Icarus\Kernel\Modules\Collectors\Routes\IlluminateRouteCollector>
     */
    private array $collectors = [];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * The class name of the collector handled by this handler.
     *
     * @return class-string<\Icarus\Kernel\Modules\Contracts\Collector>
     *
     * @phpstan-return class-string<\Icarus\Kernel\Modules\Contracts\RouteCollector>
     */
    public function collects(): string
    {
        return RouteCollector::class;
    }

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
    public function create(Module $module, Register $register): Collector
    {
        $collector = new IlluminateRouteCollector(
            $module,
            false, // TODO: Make sure this isn't hardcoded
            $register->operatingContext
        );

        return $this->collectors[] = $collector;
    }

    /**
     * Register the collected data.
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->collectors as $collector) {
            if ($collector->hasMappers() === false) {
                continue;
            }

            $this->mapMappers($collector->unscopedWebMappers, $collector, false, false);
            $this->mapMappers($collector->unscopedApiMappers, $collector, false, true);
            $this->mapMappers($collector->webMappers, $collector, true, false);
            $this->mapMappers($collector->apiMappers, $collector, true, true);
        }
    }

    /**
     * @param array<class-string<\Icarus\Kernel\Contracts\RouteMapper>>         $mappers
     * @param \Icarus\Kernel\Modules\Collectors\Routes\IlluminateRouteCollector $collector
     * @param bool                                                              $scoped
     * @param bool                                                              $api
     *
     * @return void
     */
    private function mapMappers(
        array                    $mappers,
        IlluminateRouteCollector $collector,
        bool                     $scoped,
        bool                     $api
    ): void
    {
        if (empty($mappers)) {
            return;
        }

        $routing = new RouteRegistrar($this->router);
        $name    = '';
        $prefix  = '';

        if ($api) {
            $name   .= 'api.v1.';
            $prefix .= 'api/v1/';
            $routing->middleware('api');
        }

        if ($collector->operatingContext) {
            $name   .= $collector->operatingContext->value . '.';
            $prefix .= $collector->operatingContext->value . '/';
        }

        if ($scoped) {
            $slug   = Str::slug($collector->module->ident);
            $name   .= $slug . '::';
            $prefix .= $slug . '/';
        }

        $routing->name($name)
                ->prefix($prefix)
                ->group(function () use ($mappers) {
                    foreach ($mappers as $mapper) {
                        new $mapper()->map($this->router);
                    }
                });
    }
}
