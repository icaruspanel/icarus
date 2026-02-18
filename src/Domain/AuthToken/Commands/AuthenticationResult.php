<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Commands;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use SensitiveParameter;

final readonly class AuthenticationResult
{
    public function __construct(
        public UserId                       $userId,
        #[SensitiveParameter] public string $token,
        public AuthTokenId                  $authTokenId,
        public OperatingContext             $context,
        public ?CarbonImmutable             $expiresAt = null
    )
    {
    }
}
