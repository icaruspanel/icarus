<?php
declare(strict_types=1);

namespace App\Http\Routes\Admin;

use App\Http\Controllers\LoginWithCredentials;
use App\Support\RouteMapper;
use Illuminate\Routing\Router;

final class AdminAuthRoutes implements RouteMapper
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
        $router->post('/auth', LoginWithCredentials::class);
    }
}
