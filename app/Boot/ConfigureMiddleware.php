<?php
declare(strict_types=1);

namespace App\Boot;

use App\Http\Middleware\AdminRequestsOnly;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\UserRequestsOnly;
use Closure;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

final class ConfigureMiddleware
{
    public static function make(): Closure
    {
        return new self()(...);
    }

    public function __invoke(Middleware $middleware): void
    {
        $this->setupUserMiddleware($middleware);
        $this->setupAdminMiddleware($middleware);
    }

    private function setupUserMiddleware(Middleware $middleware): void
    {
        $middleware->group('user', [
            UserRequestsOnly::class,
            SubstituteBindings::class,
        ]);

        $middleware->append(UserRequestsOnly::class);
    }

    private function setupAdminMiddleware(Middleware $middleware): void
    {
        $middleware->group('admin', [
            AdminRequestsOnly::class,
            SubstituteBindings::class,
        ]);

        $middleware->append(AdminRequestsOnly::class);
    }
}
