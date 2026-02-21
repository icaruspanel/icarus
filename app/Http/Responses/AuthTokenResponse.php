<?php
declare(strict_types=1);

namespace App\Http\Responses;

use Icarus\Domain\AuthToken\DataObjects\AuthenticationResult;

class AuthTokenResponse
{
    /**
     * @param \Icarus\Domain\AuthToken\DataObjects\AuthenticationResult $result
     *
     * @return array<string, mixed>
     */
    public static function make(AuthenticationResult $result): array
    {
        return [
            'token'      => $result->token,
            'expires_at' => $result->expiresAt?->format('Y-m-d H:i:s'),
        ];
    }
}
