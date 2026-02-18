<?php
declare(strict_types=1);

namespace App\Boot;

use App\Http\Middleware\EnforceJsonRequests;
use App\Http\Routes\Account;
use App\Http\Routes\DefaultRoutes;
use App\Http\Routes\Platform;
use Closure;
use Illuminate\Routing\Router;

final class ConfigureRouting
{
    public static function make(): Closure
    {
        return new self()(...);
    }

    /**
     * @var array<string, list<class-string<\App\Support\RouteMapper>>>
     */
    private static array $mappers = [
        'web' => [
            DefaultRoutes::class,
        ],
    ];


    /**
     * @var array<string, list<class-string<\App\Support\RouteMapper>>>
     */
    private static array $accountMappers = [
        'api' => [
            Account\AccountAuthRoutes::class,
        ],
    ];


    /**
     * @var array<string, list<class-string<\App\Support\RouteMapper>>>
     */
    private static array $platformMappers = [
        'api' => [
            Platform\PlatformAuthRoutes::class,
        ],
    ];

    public function __invoke(Router $router): void
    {
        $this->mapWeb($router, self::$mappers['web']);

        $router->name('api.v1.')
               ->prefix('/api/v1/')
               ->middleware([EnforceJsonRequests::class])
               ->group(function () use ($router) {
                   $this->mapAccountApi($router, self::$accountMappers['api']);
                   $this->mapPlatformApi($router, self::$platformMappers['api']);
               });
    }

    /**
     * @param list<class-string<\App\Support\RouteMapper>> $mappers
     */
    private function mapWeb(Router $router, array $mappers): void
    {
        foreach ($mappers as $mapper) {
            new $mapper()->map($router);
        }
    }

    /**
     * @param list<class-string<\App\Support\RouteMapper>> $mappers
     */
    private function mapAccountApi(Router $router, array $mappers): void
    {
        $router->middleware('account')
               ->prefix('account/')
               ->name('account:')
               ->group(function () use ($router, $mappers) {
                   /** @var class-string<\App\Support\RouteMapper> $mapper */
                   foreach ($mappers as $mapper) {
                       new $mapper()->map($router);
                   }
               });
    }

    /**
     * @param list<class-string<\App\Support\RouteMapper>> $mappers
     */
    private function mapPlatformApi(Router $router, array $mappers): void
    {
        $router->middleware('platform')
               ->prefix('platform/')
               ->name('platform:')
               ->group(function () use ($router, $mappers) {
                   /** @var class-string<\App\Support\RouteMapper> $mapper */
                   foreach ($mappers as $mapper) {
                       new $mapper()->map($router);
                   }
               });
    }
}
