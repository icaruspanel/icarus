<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Exceptions;

use Icarus\Domain\User\UserId;
use Throwable;

class UnableToAuthenticate extends AuthenticationFailed
{
    public function __construct(
        string     $reason,
        ?UserId    $userId = null,
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        if ($userId) {
            $message = sprintf(
                'Unable to authenticate user "%s": %s',
                $userId, $reason
            );
        } else {
            $message = sprintf(
                'Unable to authenticate user : %s',
                $reason
            );
        }

        parent::__construct($message, $code, $previous);
    }

    public static function make(string $reason, ?UserId $userId = null, ?Throwable $previous = null): self
    {
        return new self($reason, $userId, previous: $previous);
    }
}
