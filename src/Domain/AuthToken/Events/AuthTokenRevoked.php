<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Events;

use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\User\UserId;

final readonly class AuthTokenRevoked
{
    public function __construct(
        public AuthTokenId $authTokenId,
        public UserId      $userId
    )
    {
    }
}
