<?php

namespace App\Http\Middleware;

use App\Auth\TokenGuard;
use App\Enum\UserType;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * @var \App\Auth\TokenGuard
     */
    private TokenGuard $guard;

    public function __construct(UserType $userType, #[Auth('api')] TokenGuard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->guard->check() === false) {
            throw new AuthenticationException(
                'Unauthenticated.',
                ['api'],
                null,
            );
        }

        return $next($request);
    }
}
