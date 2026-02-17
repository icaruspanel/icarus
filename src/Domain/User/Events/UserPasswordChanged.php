<?php
declare(strict_types=1);

namespace Icarus\Domain\User\Events;

use Icarus\Domain\User\UserId;

final readonly class UserPasswordChanged
{
    public function __construct(
        public UserId $userId,
        public string $oldPassword,
        public string $newPassword
    )
    {
    }
}
