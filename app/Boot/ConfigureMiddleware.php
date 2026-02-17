<?php
declare(strict_types=1);

namespace App\Boot;

use Closure;
use Illuminate\Foundation\Configuration\Middleware;

final class ConfigureMiddleware
{
    public static function make(): Closure
    {
        return new self()(...);
    }

    public function __invoke(Middleware $middleware): void
    {

    }

}
