<?php
declare(strict_types=1);

namespace App\Http\Routes\Account;

use App\Http\Controllers\Account\LoginWithCredentials;
use App\Http\Controllers\Account\ShowMyDetails;
use Icarus\Kernel\Contracts\RouteMapper;
use Illuminate\Routing\Router;

final class AccountAuthRoutes implements RouteMapper
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
        $router->name('auth')
               ->post('/auth', LoginWithCredentials::class);

        $router->middleware('context.auth:account')
               ->group($this->authedRoutes(...));

        $router->middleware('context.auth:account')
               ->get('/me', ShowMyDetails::class);
    }

    private function authedRoutes(Router $router): void
    {
        $router->name('me')
               ->get('/me', ShowMyDetails::class);
    }
}
