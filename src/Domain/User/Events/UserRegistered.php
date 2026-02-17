<?php
declare(strict_types=1);

namespace Icarus\Domain\User\Events;

use Icarus\Domain\User\UserId;

final readonly class UserRegistered
{
    public function __construct(
        public UserId $userId,
        public string $name,
        public string $email
    )
    {
    }
}
