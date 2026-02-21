<?php
declare(strict_types=1);

namespace App\Http\Routes;

use Icarus\Kernel\Contracts\RouteMapper;
use Illuminate\Routing\Router;

final class DefaultRoutes implements RouteMapper
{
    /**
     * Map routes with the given router.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function map(Router $router): void
    {
        $router->get('/', function () {
            return view('welcome');
        });
    }
}
