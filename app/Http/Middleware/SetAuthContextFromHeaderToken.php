<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Exceptions\NotAuthenticated;
use App\Http\Exceptions\OutOfOperatingContext;
use Carbon\CarbonImmutable;
use Closure;
use Icarus\Domain\AuthToken\TokenPrefix;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Kernel\AuthToken\Actions\ResolveAuthToken;
use Icarus\Kernel\Icarus;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAuthContextFromHeaderToken
{
    /**
     * @var \Icarus\Kernel\Icarus
     */
    private Icarus $icarus;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private AuthManager $auth;

    /**
     * @var \Icarus\Kernel\AuthToken\Actions\ResolveAuthToken
     */
    private ResolveAuthToken $query;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private Container $container;

    public function __construct(
        Icarus           $icarus,
        ResolveAuthToken $query,
        AuthManager      $auth,
        Container        $container
    )
    {
        $this->icarus    = $icarus;
        $this->query     = $query;
        $this->auth      = $auth;
        $this->container = $container;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // The custom guard we use isn't API specific, but this middleware is,
        // so we need to make sure that the default auth guard is the API one
        $this->auth->shouldUse('api');

        $rawToken = $request->bearerToken();

        // If there's no token, error
        if (empty($rawToken)) {
            throw new NotAuthenticated('Token missing');
        }

        // Identify the context from the token
        $context = TokenPrefix::resolve($rawToken);

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
