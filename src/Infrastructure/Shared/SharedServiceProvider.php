<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMaps();
    }

    private function registerMaps(): void
    {
        $this->app->singleton(IdentityMap::class);
        $this->app->singleton(SnapshotMap::class);
    }
}
