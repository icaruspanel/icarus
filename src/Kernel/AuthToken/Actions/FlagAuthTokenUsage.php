<?php
declare(strict_types=1);

namespace Icarus\Kernel\AuthToken\Actions;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\AuthTokenRepository;

final readonly class FlagAuthTokenUsage
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

    public function execute(
        AuthTokenId     $authTokenId,
        CarbonImmutable $now
    ): void
    {
        $token = $this->authTokenRepository->find($authTokenId);

        if ($token === null) {
            return;
        }

        $token->updateUsedAt($now);

        $this->authTokenRepository->save($token);
    }
}
