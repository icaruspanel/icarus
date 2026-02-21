<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Kernel\Icarus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetOperatingContext
{
    /**
     * @var \Icarus\Kernel\Icarus
     */
    private Icarus $icarus;

    public function __construct(Icarus $icarus)
    {
        $this->icarus = $icarus;
    }

    public function handle(Request $request, Closure $next, string $contextName): Response
    {
        $context = OperatingContext::from($contextName);

        $this->icarus->setOperatingContext($context);

        /** @var \Symfony\Component\HttpFoundation\Response */
        return $next($request);
    }
}
