<?php
declare(strict_types=1);

namespace App\Boot;

use App\Http\Middleware\EnforceJsonRequests;
use App\Http\Routes\Admin;
use App\Http\Routes\DefaultRoutes;
use App\Http\Routes\User;
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
    private static array $ApiMappers = [
        'user'  => [
            User\UserAuthRoutes::class,
        ],
        'admin' => [
            Admin\AdminAuthRoutes::class,
        ],
    ];

    public function __invoke(Router $router): void
    {
        foreach (self::$mappers as $group => $mappers) {
            $method = 'map' . ucfirst($group);

            if (method_exists($this, $method)) {
                $this->$method($router, $mappers);
            }
        }

        $router->name('api.v1.')
               ->prefix('/api/v1/')
               ->middleware([EnforceJsonRequests::class])
               ->group(function () use ($router) {
                   foreach (self::$ApiMappers as $group => $mappers) {
                       $method = 'map' . ucfirst($group) . 'Api';

                       if (method_exists($this, $method)) {
                           $this->$method($router, $mappers);
                       }
                   }
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
    private function mapUserApi(Router $router, array $mappers): void
    {
        $router->middleware('user')
               ->prefix('user/')
               ->name('user:')
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
    private function mapAdminApi(Router $router, array $mappers): void
    {
        $router->middleware('admin')
               ->prefix('/admin/')
               ->name('admin:')
               ->group(function () use ($router, $mappers) {
                   /** @var class-string<\App\Support\RouteMapper> $mapper */
                   foreach ($mappers as $mapper) {
                       new $mapper()->map($router);
                   }
               });
    }
}
