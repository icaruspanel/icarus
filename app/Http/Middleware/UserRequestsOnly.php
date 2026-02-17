<?php

namespace App\Http\Middleware;

use App\Enum\UserType;
use App\Support\StateManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRequestsOnly
{
    /**
     * @var \App\Support\StateManager
     */
    private StateManager $manager;

    /**
     * @param \App\Support\StateManager $manager
     */
    public function __construct(StateManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->manager->setState(UserType::User);

        return $next($request);
    }
}
