<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Exceptions;

class InvalidCredentials extends AuthenticationFailed
{
    public static function make(): self
    {
        return new self('Invalid authentication credentials');
    }
}
