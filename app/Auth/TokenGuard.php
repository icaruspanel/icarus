<?php
declare(strict_types=1);

namespace App\Auth;

use App\Enum\UserType;
use App\Models\AuthToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use RuntimeException;

class TokenGuard implements Guard
{
    use GuardHelpers;

    /**
     * @var non-empty-string
     */
    public readonly string $name;

    private Request $request;

    private ?AuthToken $token;

    /**
     * @param non-empty-string                        $name
     * @param \Illuminate\Http\Request                $request
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     */
    public function __construct(
        string       $name,
        Request      $request,
        UserProvider $provider,
    )
    {
        $this->name     = $name;
        $this->provider = $provider;

        $this->setRequest($request);
    }

    private function getTokenForRequest(): ?AuthToken
    {
        /** @var string $token */
        $token = $this->request->bearerToken();

        // If the token is empty, we exit early
        if (empty($token)) {
            return null;
        }

        // Grab the prefix, so we can determine what sort of request this
        // really is
        $prefix = substr($token, 0, 4);

        if ($prefix === 'usr_') {
            $type = UserType::User;
        } else if ($prefix === 'adm_') {
            $type = UserType::Admin;
        } else {
            // If it's a type we don't know, we'll skip it and fail silently
            return null;
        }

        $authToken = AuthToken::query()
                              ->where('type', $type)
                              ->where('token', $token)
                              ->first();

        // If the token doesn't exist, we'll exit early
        if ($authToken === null) {
            return null;
        }

        // Otherwise we need to check if it has expired or was revoked
        $now = CarbonImmutable::now();

        if ($authToken->hasExpired($now)) {
            return null;
        }

        if ($authToken->wasRevoked($now)) {
            return null;
        }

        $this->touchToken($authToken);

        return $authToken;
    }

    private function touchToken(AuthToken $authToken): void
    {
        $authToken->last_used_at = CarbonImmutable::now();
        $authToken->save();
    }

    public function token(): ?AuthToken
    {
        return $this->token;
    }

    public function user(): ?Authenticatable
    {
        if ($this->user === null) {
            $token = $this->getTokenForRequest();

            if ($token !== null) {
                $this->token = $token;
                $this->user  = $this->provider->retrieveById($token->user_id);
            }
        }

        return $this->user;
    }

    /**
     * Set the current user and generate a new token.
     */
    public function auth(Authenticatable $user, UserType $type): self
    {
        if (! $user instanceof User) {
            throw new RuntimeException('Something has gone very wrong, the user is the wrong type');
        }

        if ($type === UserType::Admin && $user->is_admin === false) {
            throw new RuntimeException('You are not authorized to perform this action');
        }

        $this->user  = $user;
        $this->token = new AuthToken([
            'type' => $type,
        ]);

        $this->token->user()->associate($user);
        $this->token->save();

        return $this;
    }

    /**
     * @param array<string, string> $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        if (isset($credentials['email'], $credentials['password'])) {
            $user = $this->provider->retrieveByCredentials($credentials);

            if ($user !== null) {
                return $this->provider->validateCredentials($user, $credentials);
            }
        }

        return false;
    }

    /**
     * Set the current request instance.
     *
     * @return $this
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Forget the current user.
     *
     * @return $this
     */
    public function forgetUser(): self
    {
        $this->user = $this->token = null;

        return $this;
    }
}
