<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;

/**
 * @phpstan-type AuthTokenData array{id: string, user_id: string, context: string, selector: string, secret: string, user_agent: string|null, ip: string|null, last_used_at: string|null, expires_at: string|null, revoked_at: string|null, revoked_reason: string|null}
 */
final readonly class AuthTokenHydrator
{
    /**
     * @param array<string, mixed>  $data
     *
     * @phpstan-param AuthTokenData $data
     *
     * @return \Icarus\Domain\AuthToken\AuthToken
     */
    public function hydrate(array $data): AuthToken
    {
        return new AuthToken(
            new AuthTokenId($data['id']),
            new StoredToken($data['selector'], $data['secret']),
            new UserId($data['user_id']),
            OperatingContext::from($data['context']),
            new Device($data['user_agent'], $data['ip']),
            $data['last_used_at'] ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $data['last_used_at']) : null,
            $data['expires_at'] ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $data['expires_at']) : null,
            $data['revoked_at'] ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $data['revoked_at']) : null,
            $data['revoked_reason']
        );
    }

    /**
     * @param \Icarus\Domain\AuthToken\AuthToken $token
     *
     * @return array<string, mixed>
     *
     * @phpstan-return AuthTokenData
     */
    public function dehydrate(AuthToken $token): array
    {
        return [
            'id'             => $token->id->id,
            'user_id'        => $token->userId->id,
            'context'        => $token->context->value,
            'selector'       => $token->token->selector,
            'secret'         => $token->token->secret,
            'user_agent'     => $token->device->userAgent,
            'ip'             => $token->device->ip,
            'last_used_at'   => $token->lastUsedAt?->format('Y-m-d H:i:s'),
            'expires_at'     => $token->expiresAt?->format('Y-m-d H:i:s'),
            'revoked_at'     => $token->revokedAt?->format('Y-m-d H:i:s'),
            'revoked_reason' => $token->revokedReason,
        ];
    }
}
