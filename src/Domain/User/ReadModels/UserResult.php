<?php
declare(strict_types=1);

namespace Icarus\Domain\User\ReadModels;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\UserId;

final readonly class UserResult
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
