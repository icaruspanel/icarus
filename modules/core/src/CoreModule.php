<?php
declare(strict_types=1);

namespace Icarus\Modules\Core;

use Icarus\Domain\Shared\OperatingContext;
use Icarus\Kernel\Modules\Attributes\Module;
use Icarus\Kernel\Modules\Attributes\Register;
use Icarus\Kernel\Modules\Attributes\NoContext;
use Icarus\Kernel\Modules\Contracts\RouteCollector;
use Icarus\Modules\Core\Http\Routes;

#[Module(self::IDENT)]
final class CoreModule
{
    public const string IDENT = 'core';

    /**
     * Register routes for out of context.
     *
     * @param \Icarus\Kernel\Modules\Contracts\RouteCollector $collector
     *
     * @return void
     */
    #[Register, NoContext]
    public function registerOutOfContextRoutes(RouteCollector $collector): void
    {
        // Create /api/v1/{...} routes
    }

    /**
     * Register routes for account context.
     *
     * @param \Icarus\Kernel\Modules\Contracts\RouteCollector $collector
     *
     * @return void
     */
    #[Register(OperatingContext::Account), NoContext]
    public function registerAccountRoutes(RouteCollector $collector): void
    {
        // Create /api/v1/account/{...} routes
        $collector->unscopedApi(
            Routes\Api\Account\AccountAuthRoutes::class
        );
    }

    /**
     * Register routes for platform context.
     *
     * @param \Icarus\Kernel\Modules\Contracts\RouteCollector $collector
     *
     * @return void
     */
    #[Register(OperatingContext::Platform), NoContext]
    public function registerPlatformRoutes(RouteCollector $collector): void
    {
        // Create /api/v1/platform/{...} routes
        $collector->unscopedApi(
            Routes\Api\Platform\PlatformAuthRoutes::class,
        );
    }
}
