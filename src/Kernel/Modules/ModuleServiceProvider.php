<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules;

use Icarus\Kernel\Modules\Collectors\CollectorRegistry;
use Icarus\Kernel\Modules\Collectors\Routes\RouteCollectorHandler;
use Icarus\Kernel\Modules\Contracts\RouteCollector;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRegistry();
        $this->registerCollectors();
    }

    private function registerRegistry(): void
    {
        $this->app->singleton(ModuleRegistry::class);
    }

    private function registerCollectors(): void
    {
        $this->app->singleton(CollectorRegistry::class);

        $this->callAfterResolving(CollectorRegistry::class, function (CollectorRegistry $registry) {
            // Register the route collector.
            $registry->register($this->app->make(RouteCollectorHandler::class));
        });
    }

    public function boot(): void
    {
        $this->app->call([$this, 'bootModuleRoutes']);
    }

    private function bootModuleRoutes(ModuleRegistry $modules): void
    {
        $modules->collect(RouteCollector::class);
    }
}
