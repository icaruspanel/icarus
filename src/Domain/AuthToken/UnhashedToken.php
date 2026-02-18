<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken;

use SensitiveParameter;

final readonly class UnhashedToken
{
    public function __construct(
        #[SensitiveParameter] public string $unhashedToken,
        public StoredToken                  $token,
    )
    {
    }
}
