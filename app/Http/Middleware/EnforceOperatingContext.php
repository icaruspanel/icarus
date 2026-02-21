<?php

namespace App\Http\Middleware;

use App\Http\Exceptions\NotAuthenticated;
use Closure;
use Icarus\Kernel\Icarus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceOperatingContext
{
    /**
     * @var \Icarus\Kernel\Icarus
     */
    private Icarus $icarus;

    public function __construct(Icarus $icarus)
    {
        $this->icarus = $icarus;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->icarus->hasOperatingContext() && $this->icarus->hasAuthContext()) {
            $operatingContext = $this->icarus->getOperatingContext();
            /** @var \Icarus\Domain\Shared\AuthContext $authContext */
            $authContext = $this->icarus->getAuthContext();


            if ($operatingContext !== $authContext->context) {
                throw new NotAuthenticated('Invalid token context');
            }
        }

        return $next($request);
    }
}
