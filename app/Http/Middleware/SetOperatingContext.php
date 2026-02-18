<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Icarus;
use Closure;
use Icarus\Domain\Shared\OperatingContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetOperatingContext
{
    /**
     * @var \App\Icarus
     */
    private Icarus $icarus;

    public function __construct(Icarus $icarus)
    {
        $this->icarus = $icarus;
    }

    public function handle(Request $request, Closure $next, string $contextName): Response
    {
        $context = OperatingContext::from($contextName);

        $this->icarus->setContext($context);

        /** @var \Symfony\Component\HttpFoundation\Response */
        return $next($request);
    }
}
