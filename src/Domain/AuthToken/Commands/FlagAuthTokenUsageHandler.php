<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Commands;

use Icarus\Domain\AuthToken\AuthTokenRepository;

final readonly class FlagAuthTokenUsageHandler
{
    /**
     * @var \Icarus\Domain\AuthToken\AuthTokenRepository
     */
    private AuthTokenRepository $authTokenRepository;

    public function __construct(
        AuthTokenRepository $authTokenRepository
    )
    {
        $this->authTokenRepository = $authTokenRepository;
    }

    public function handle(FlagAuthTokenUsage $command): void
    {
        $token = $this->authTokenRepository->find($command->authTokenId);

        if ($token === null) {
            return;
        }

        $token->updateUsedAt($command->now);

        $this->authTokenRepository->save($token);
    }
}
