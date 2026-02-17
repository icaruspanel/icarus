<?php
declare(strict_types=1);

namespace App\Enum;

enum UserType: string
{
    case User = 'user';

    case Admin = 'admin';

    public function prefix(): string
    {
        return match ($this) {
            self::User  => 'usr_',
            self::Admin => 'adm_',
        };
    }
}
