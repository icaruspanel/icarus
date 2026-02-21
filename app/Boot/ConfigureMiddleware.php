<?php
declare(strict_types=1);

namespace App\Boot;

use App\Http\Middleware\EnforceJsonRequests;
use App\Http\Middleware\EnforceOperatingContext;
use App\Http\Middleware\SetAuthContextFromHeaderToken;
use App\Http\Middleware\SetOperatingContext;
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
        $this->setupAliases($middleware);
        $this->setupApiMiddleware($middleware);
        $this->setupAccountMiddleware($middleware);
        $this->setupPlatformMiddleware($middleware);
    }

    private function setupApiMiddleware(Middleware $middleware): void
    {
        $middleware->group('api', [
            SubstituteBindings::class,
            EnforceJsonRequests::class,
        ]);
    }

    private function setupAliases(Middleware $middleware): void
    {
        $middleware->alias([
            'context.auth'      => SetAuthContextFromHeaderToken::class,
            'context.operating' => SetOperatingContext::class,
            'context.enforce'   => EnforceOperatingContext::class,
        ]);
    }

    private function setupAccountMiddleware(Middleware $middleware): void
    {
        $middleware->group('account', [
            SubstituteBindings::class,
            'context.operating:account',
            'context.enforce',
        ]);
    }

    private function setupPlatformMiddleware(Middleware $middleware): void
    {
        $middleware->group('platform', [
            SubstituteBindings::class,
            'context.operating:platform',
            'context.enforce',
        ]);
    }
}
