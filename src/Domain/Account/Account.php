<?php
declare(strict_types=1);

namespace Icarus\Domain\Account;

final class Account
{
    public readonly AccountId $id;

    private(set) string $name;

    public function __construct(
        AccountId $id,
        string    $name
    )
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
