<?php
declare(strict_types=1);

namespace Icarus\Domain\User;

use Carbon\CarbonImmutable;

final class UserEmail
{
    public static function create(string $email, ?CarbonImmutable $verifiedAt = null): self
    {
        return $verifiedAt ? self::verified($email, $verifiedAt) : self::unverified($email);
    }

    public static function unverified(string $email): self
    {
        return new self($email);
    }

    public static function verified(string $email, CarbonImmutable $verifiedAt): self
    {
        return new self($email, true, $verifiedAt);
    }

    public readonly string $email;

    private(set) bool $verified = false;

    private(set) ?CarbonImmutable $verifiedAt;

    public function __construct(
        string           $email,
        bool             $verified = false,
        ?CarbonImmutable $verifiedAt = null
    )
    {
        $this->email      = $email;
        $this->verified   = $verified;
        $this->verifiedAt = $verifiedAt;
    }
}
