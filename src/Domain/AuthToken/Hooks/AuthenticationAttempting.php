<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Hooks;

use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\Shared\CanBeCancelled;
use Icarus\Domain\Shared\Cancellable;
use Icarus\Domain\Shared\OperatingContext;

final class AuthenticationAttempting implements Cancellable
{
    use CanBeCancelled;

    public function __construct(
        public readonly string $email,
        public readonly Device $device,
        public readonly OperatingContext $context
    )
    {
    }
}
