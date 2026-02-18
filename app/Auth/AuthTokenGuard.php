<?php
declare(strict_types=1);

namespace App\Auth;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsage;
use Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsageHandler;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Infrastructure\User\Queries\GetUserById;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class AuthTokenGuard implements Guard
{
    use GuardHelpers;

    /**
     * @var non-empty-string
     */
    public readonly string $name;

    private GetUserById $userResolver;

    private FlagAuthTokenUsageHandler $flagTokenUsage;

    private(set) ?AuthContext $authContext = null;

    /**
     * @param non-empty-string                                            $name
     * @param \Icarus\Infrastructure\User\Queries\GetUserById             $userResolver
     * @param \Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsageHandler $flagTokenUsage
     * @param \Icarus\Domain\Shared\AuthContext|null                      $authContext
     */
    public function __construct(
        string                    $name,
        GetUserById               $userResolver,
        FlagAuthTokenUsageHandler $flagTokenUsage,
        ?AuthContext              $authContext,
    )
    {
        $this->name           = $name;
        $this->userResolver   = $userResolver;
        $this->flagTokenUsage = $flagTokenUsage;

        $this->setAuthContext($authContext);
    }

    public function setAuthContext(?AuthContext $context): void
    {
        $this->authContext = $context;

        if ($context !== null) {
            $this->flagTokenUsage->handle(new FlagAuthTokenUsage(
                $context->authTokenId,
                CarbonImmutable::now(),
            ));
        }
    }

    public function user(): ?Authenticatable
    {
        if ($this->user === null && $this->authContext !== null) {
            $result = $this->userResolver->execute($this->authContext->userId);

            if ($result !== null) {
                $this->user = AuthenticatedUser::create($result, $this->authContext);
            }
        }

        return $this->user;
    }

    /**
     * @param array<string, string> $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }
}
