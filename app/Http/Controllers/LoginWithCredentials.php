<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\TokenGuard;
use App\Enum\UserType;
use App\Http\Requests\LoginWithCredentialsRequest;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\JsonResponse;

final readonly class LoginWithCredentials
{
    /**
     * @var \App\Auth\TokenGuard
     */
    private TokenGuard $guard;

    /**
     * @var \App\Enum\UserType
     */
    private UserType $userType;

    public function __construct(UserType $userType, #[Auth('api')] TokenGuard $guard)
    {
        $this->userType = $userType;
        $this->guard    = $guard;
    }

    /**
     * @param \App\Http\Requests\LoginWithCredentialsRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(LoginWithCredentialsRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validated();

        if ($this->guard->validate($credentials)) {
            $user = User::query()->where('email', $credentials['email'])->firstOrFail();

            $this->guard->auth($user, $this->userType);

            /** @var \App\Models\AuthToken $token */
            $token = $this->guard->token();

            return response()->json([
                'message' => 'Logged in successfully.',
                'token'   => $token->token,
            ]);
        }

        return response()->json(['message' => 'Invalid credentials.'], 401);
    }
}
