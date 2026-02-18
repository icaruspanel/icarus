<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Events;

use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;

final readonly class UserAuthenticated
{
    public function __construct(
        public UserId $userId,
        public AuthTokenId $authTokenId,
        public Device $device,
        public OperatingContext $context
    )
    {
    }
}
