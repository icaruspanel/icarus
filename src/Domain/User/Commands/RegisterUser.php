<?php
declare(strict_types=1);

namespace Icarus\Domain\User\Commands;

use Carbon\CarbonImmutable;
use SensitiveParameter;

final readonly class RegisterUser
{
    public function __construct(
        public string                       $name,
        public string                       $email,
        #[SensitiveParameter] public string $password,
        public ?CarbonImmutable             $verifiedAt = null
    )
    {
    }
}
