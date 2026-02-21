<?php
declare(strict_types=1);

namespace Icarus\Kernel\Auth;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Kernel\AuthToken\Actions\FlagAuthTokenUsage;
use Icarus\Kernel\User\Actions\GetUserById;
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

    private FlagAuthTokenUsage $flagTokenUsage;

    private(set) ?AuthContext $authContext = null;

    /**
     * @param non-empty-string                                    $name
     * @param \Icarus\Kernel\User\Actions\GetUserById             $userResolver
     * @param \Icarus\Kernel\AuthToken\Actions\FlagAuthTokenUsage $flagTokenUsage
     * @param \Icarus\Domain\Shared\AuthContext|null              $authContext
     */
    public function __construct(
        string             $name,
        GetUserById        $userResolver,
        FlagAuthTokenUsage $flagTokenUsage,
        ?AuthContext       $authContext,
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
            $this->flagTokenUsage->execute(
                $context->authTokenId,
                CarbonImmutable::now(),
            );
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
