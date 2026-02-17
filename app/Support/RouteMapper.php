<?php

namespace App\Support;

use Illuminate\Routing\Router;

/**
 * Route Mapper
 *
 * Route mappers are an object-first approach to defining routes.
 */
interface RouteMapper
{
    /**
     * Map routes with the given router.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function map(Router $router): void;
}
