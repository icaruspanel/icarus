<?php
declare(strict_types=1);

namespace App\Boot;

use Closure;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

final class ConfigureExceptions
{
    public static function make(): Closure
    {
        return new self()(...);
    }

    public function __invoke(Exceptions $exceptions): void
    {

    }

}
