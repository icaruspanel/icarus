<?php
declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Exceptions\OutOfOperatingContext;
use App\Http\Requests\LoginWithCredentialsRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\AuthTokenResponse;
use App\Icarus;
use Icarus\Domain\AuthToken\Commands\AuthenticateUser;
use Icarus\Domain\AuthToken\Commands\AuthenticateUserHandler;
use Icarus\Domain\AuthToken\Device;
use Illuminate\Http\JsonResponse;

final readonly class LoginWithCredentials
{
    /**
     * @var \App\Icarus
     */
    private Icarus $icarus;

    /**
     * @var \Icarus\Domain\AuthToken\Commands\AuthenticateUserHandler
     */
    private AuthenticateUserHandler $authenticate;

    public function __construct(Icarus $icarus, AuthenticateUserHandler $authenticate)
    {
        $this->icarus       = $icarus;
        $this->authenticate = $authenticate;
    }

    /**
     * @param \App\Http\Requests\LoginWithCredentialsRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(LoginWithCredentialsRequest $request): JsonResponse
    {
        // This shouldn't ever be hit, in theory, but it's here just in case
        if ($this->icarus->hasContext() === false) {
            throw new OutOfOperatingContext('Operating context missing');
        }

        /** @var \Icarus\Domain\Shared\OperatingContext $context */
        $context = $this->icarus->getContext();

        /** @var array{email: string, password: string, user_agent?: string|null, ip?: string|null} $credentials */
        $credentials = $request->validated();

        $authentication = $this->authenticate->handle(new AuthenticateUser(
            $credentials['email'],
            $credentials['password'],
            $context,
            new Device(
                $credentials['user_agent'] ?? $request->userAgent(),
                    $credentials['ip'] ?? $request->ip()
            )
        ));

        return ApiResponse::item(AuthTokenResponse::make($authentication));
    }
}
