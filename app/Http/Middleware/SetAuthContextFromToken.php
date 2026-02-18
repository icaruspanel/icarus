<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Exceptions\NotAuthenticated;
use App\Http\Exceptions\OutOfOperatingContext;
use App\Icarus;
use Carbon\CarbonImmutable;
use Closure;
use Icarus\Domain\AuthToken\TokenPrefix;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Infrastructure\AuthToken\Queries\ResolveAuthToken;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAuthContextFromToken
{
    /**
     * @var \App\Icarus
     */
    private Icarus $icarus;

    /**
     * @var \Icarus\Infrastructure\AuthToken\Queries\ResolveAuthToken
     */
    private ResolveAuthToken $query;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private Container $container;

    public function __construct(
        Icarus $icarus,
        ResolveAuthToken $query,
        Container        $container
    )
    {
        $this->icarus    = $icarus;
        $this->query     = $query;
        $this->container = $container;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // This shouldn't ever be hit, in theory, but it's here just in case
        if ($this->icarus->hasContext() === false) {
            throw new OutOfOperatingContext('Operating context missing');
        }

        // Get the current operating context
        $currentContext = $this->icarus->getContext();

        $rawToken = $request->bearerToken();

        // If there's no token, error
        if (empty($rawToken)) {
            throw new NotAuthenticated('Token missing');
        }

        // Identify the context from the token
        $context = TokenPrefix::resolve($rawToken);

        // If the context is null or doesn't match the current, error
        if ($context !== $currentContext) {
            throw new NotAuthenticated('Invalid token context');
        }

        // Strip the token prefix
        $token = TokenPrefix::strip($rawToken);

        // If we couldn't strip the prefix, then the token is invalid, so it's
        // an error
        if ($token === null) {
            throw new NotAuthenticated('Invalid token');
        }

        // Grab the components from the token
        $selector = substr($token, 0, 8);
        $secret   = substr($token, 8);

        // Run the query
        $result = $this->query->execute($selector);

        $now = CarbonImmutable::now();

        // If there's no result, the context is wrong, the secret doesn't
        // match, it has expired, or it was revoked, it's an error
        if (
            $result === null
            || $result->context !== $context
            || $result->token->verify($selector, $secret) === false
            || $result->hasExpired($now)
            || $result->wasRevoked($now)
        ) {
            throw new NotAuthenticated('Invalid token');
        }

        // If we're here, it all went well, so create a scoped binding for
        // the auth context
        $this->container->scoped(AuthContext::class, fn () => new AuthContext(
            $result->userId,
            $result->authTokenId,
            $context
        ));

        /** @var Response */
        return $next($request);
    }
}
