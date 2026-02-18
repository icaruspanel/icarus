<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Icarus\Domain\Shared\EventDispatcher;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMaps();
        $this->registerEventDispatcher();
    }

    private function registerMaps(): void
    {
        $this->app->singleton(IdentityMap::class);
        $this->app->singleton(SnapshotMap::class);
    }

    private function registerEventDispatcher(): void
    {
        $this->app->singleton(EventDispatcher::class, IlluminateEventDispatcher::class);
    }
}
