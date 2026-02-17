<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceJsonRequests
{
    /**
     * @var array<string>
     */
    private static array $except = [];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::$except) === false && $request->isJson() === false) {
            return response()->json(['message' => 'Only JSON requests are accepted.'], 400);
        }

        return $next($request);
    }
}
