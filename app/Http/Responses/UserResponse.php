<?php
declare(strict_types=1);

namespace App\Http\Responses;

use Icarus\Kernel\Auth\AuthenticatedUser;

final class UserResponse
{
    /**
     * @param \Icarus\Kernel\Auth\AuthenticatedUser $user
     *
     * @return array<string, mixed>
     */
    public static function make(AuthenticatedUser $user): array
    {
        return [
            'id'    => $user->authContext->userId->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
}
