<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\User\UserId;

final readonly class AuthContext
{
    public function __construct(
        public UserId           $userId,
        public AuthTokenId      $authTokenId,
        public OperatingContext $context
    )
    {
    }
}
