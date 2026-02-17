<?php
declare(strict_types=1);

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class DevServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerTelescope();
        $this->registerIdeHelper();
    }

    private function registerTelescope(): void
    {
        if (class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    private function registerIdeHelper(): void
    {
        if (class_exists(IdeHelperServiceProvider::class)) {
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }
}
