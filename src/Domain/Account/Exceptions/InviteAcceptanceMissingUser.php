<?php
declare(strict_types=1);

namespace Icarus\Domain\Account\Exceptions;

use DomainException;

class InviteAcceptanceMissingUser extends DomainException
{
    public static function make(): self
    {
        return new self('Invite acceptance is missing user');
    }
}
