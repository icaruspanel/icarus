<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Commands;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;

final readonly class FlagAuthTokenUsage
{
    public function __construct(
        public AuthTokenId     $authTokenId,
        public CarbonImmutable $now
    )
    {
    }
}
