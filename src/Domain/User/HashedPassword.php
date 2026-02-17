<?php
declare(strict_types=1);

namespace Icarus\Domain\User;

use SensitiveParameter;

final class HashedPassword
{
    public static function from(#[SensitiveParameter] string $password): self
    {
        return new self(password_hash($password, PASSWORD_ARGON2ID));
    }

    public function __construct(public string $hash)
    {
    }

    public function verify(#[SensitiveParameter] string $password): bool
    {
        return password_verify($password, $this->hash);
    }
}
