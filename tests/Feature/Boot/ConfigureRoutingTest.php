<?php
declare(strict_types=1);

namespace Tests\Feature\Boot;

use App\Http\Controllers\Account\LoginWithCredentials;
use App\Http\Controllers\Account\ShowMyDetails;
use App\Http\Middleware\EnforceJsonRequests;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('feature'), Group('boot'), Group('routing')]
class ConfigureRoutingTest extends TestCase
{
    // ——————————————————————————————————————————————
    // web routes
    // ——————————————————————————————————————————————

    #[Test]
    public function welcomeRouteExists(): void
    {
        $route = $this->app->make('router')->getRoutes()->match(
            $this->app->make('request')->create('/', 'GET')
        );

        $this->assertNotNull($route);
        $this->assertSame('/', $route->uri());
        $this->assertContains('GET', $route->methods());
    }

    // ——————————————————————————————————————————————
    // API prefix and middleware
    // ——————————————————————————————————————————————

    #[Test]
    public function apiRoutesAreUnderV1Prefix(): void
    {
        $router = $this->app->make('router');

        $routes = collect($router->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/'))
            ->all();

        $this->assertNotEmpty($routes);
    }

    #[Test]
    public function apiRoutesHaveEnforceJsonMiddleware(): void
    {
        $router = $this->app->make('router');

        $routes = collect($router->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/'));

        foreach ($routes as $route) {
            $this->assertContains(
                EnforceJsonRequests::class,
                $route->gatherMiddleware(),
                "Route [{$route->uri()}] missing EnforceJsonRequests middleware",
            );
        }
    }

    // ——————————————————————————————————————————————
    // account routes
    // ——————————————————————————————————————————————

    #[Test]
    public function accountAuthRouteExists(): void
    {
        $route = $this->app->make('router')->getRoutes()->getByName('api.v1.account:auth');

        $this->assertNotNull($route);
        $this->assertSame('api/v1/account/auth', $route->uri());
        $this->assertContains('POST', $route->methods());
        $this->assertSame(LoginWithCredentials::class, $route->getAction('controller'));
    }

    #[Test]
    public function accountMeRouteExists(): void
    {
        $router = $this->app->make('router');

        $route = $router->getRoutes()->match(
            $this->app->make('request')->create('/api/v1/account/me', 'GET')
        );

        $this->assertNotNull($route);
        $this->assertSame('api/v1/account/me', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertSame(ShowMyDetails::class, $route->getAction('controller'));
    }

    #[Test]
    public function accountRoutesHaveAccountMiddlewareGroup(): void
    {
        $router = $this->app->make('router');

        $routes = collect($router->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/account/'));

        foreach ($routes as $route) {
            $this->assertContains(
                'account',
                $route->gatherMiddleware(),
                "Route [{$route->uri()}] missing 'account' middleware group",
            );
        }
    }

    // ——————————————————————————————————————————————
    // platform routes
    // ——————————————————————————————————————————————

    #[Test]
    public function platformAuthRouteExists(): void
    {
        $route = $this->app->make('router')->getRoutes()->getByName('api.v1.platform:auth');

        $this->assertNotNull($route);
        $this->assertSame('api/v1/platform/auth', $route->uri());
        $this->assertContains('POST', $route->methods());
        $this->assertSame(LoginWithCredentials::class, $route->getAction('controller'));
    }

    #[Test]
    public function platformMeRouteExists(): void
    {
        $router = $this->app->make('router');

        $route = $router->getRoutes()->match(
            $this->app->make('request')->create('/api/v1/platform/me', 'GET')
        );

        $this->assertNotNull($route);
        $this->assertSame('api/v1/platform/me', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertSame(ShowMyDetails::class, $route->getAction('controller'));
    }

    #[Test]
    public function platformRoutesHavePlatformMiddlewareGroup(): void
    {
        $router = $this->app->make('router');

        $routes = collect($router->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/platform/'));

        foreach ($routes as $route) {
            $this->assertContains(
                'platform',
                $route->gatherMiddleware(),
                "Route [{$route->uri()}] missing 'platform' middleware group",
            );
        }
    }
}
