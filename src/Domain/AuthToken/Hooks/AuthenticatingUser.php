<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Hooks;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\UserId;

final readonly class AuthenticatingUser
{
    public function __construct(
        public UserId           $userId,
        public string           $name,
        public string           $email,
        public ?CarbonImmutable $verifiedAt = null
    )
    {
    }
}
