<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken;

final readonly class Device
{
    public function __construct(
        public ?string $userAgent = null,
        public ?string $ip = null
    )
    {
    }
}
