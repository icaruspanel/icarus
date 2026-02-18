<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken;

use Icarus\Domain\Shared\OperatingContext;

final class StoredToken
{
    /**
     * @throws \Random\RandomException
     */
    public static function create(OperatingContext $context): UnhashedToken
    {
        $token = bin2hex(random_bytes(32));

        return new UnhashedToken(
            TokenPrefix::for($context) . $token,
            new self(
                substr($token, 0, 8),
                hash('sha256', substr($token, 8))
            )
        );
    }

    public function __construct(
        public string $selector,
        public string $secret
    )
    {
    }

    public function verify(string $selector, string $secret): bool
    {
        return $this->selector === $selector
               && hash_equals($this->secret, hash('sha256', $secret));
    }
}
