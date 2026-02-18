<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Commands;

use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\Shared\OperatingContext;
use SensitiveParameter;

final readonly class AuthenticateUser
{
    public function __construct(
        public string                       $email,
        #[SensitiveParameter] public string $password,
        public OperatingContext             $context,
        public Device                       $device = new Device()
    )
    {
    }
}
