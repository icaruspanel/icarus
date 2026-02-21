<?php
declare(strict_types=1);

namespace Icarus\Domain\Account\Exceptions;

use DomainException;
use Icarus\Domain\User\UserId;

class InvalidUserForInvite extends DomainException
{
    public static function make(UserId $invited, UserId $accepted): self
    {
        return new self(
            sprintf('User %s cannot accept an invite for user %s', $accepted, $invited)
        );
    }
}
