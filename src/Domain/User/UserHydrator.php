<?php
declare(strict_types=1);

namespace Icarus\Domain\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\OperatingContext;

/**
 * @phpstan-type UserData array{
 *     id: string,
 *     name: string,
 *     email: string,
 *     password: string,
 *     active: bool,
 *     operates_in: string,
 *     verified_at: ?string
 * }
 */
final class UserHydrator
{
    /**
     * Hydrate a user from its raw data.
     *
     * @param array            $data
     *
     * @phpstan-param UserData $data
     *
     * @return \Icarus\Domain\User\User
     */
    public function hydrate(array $data): User
    {
        $verifiedAt = $data['verified_at'] ?? null;

        if ($verifiedAt !== null) {
            $verifiedAt = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $verifiedAt);
        }

        return new User(
            new UserId($data['id']),
            $data['name'],
            UserEmail::create($data['email'], $verifiedAt),
            new HashedPassword($data['password']),
            array_values(array_map(
                OperatingContext::from(...),
                is_string($data['operates_in']) ? json_decode($data['operates_in'], true) : $data['operates_in']
            )),
            (bool)$data['active']
        );
    }

    /**
     * Dehydrate a user into its raw data.
     *
     * @param \Icarus\Domain\User\User $user
     *
     * @return array<string, mixed>
     *
     * @phpstan-return UserData
     * @throws \JsonException
     */
    public function dehydrate(User $user): array
    {
        return [
            'id'          => $user->id->id,
            'name'        => $user->name,
            'email'       => $user->email->email,
            'password'    => $user->password->hash,
            'operates_in' => json_encode(
                array_map(static fn (OperatingContext $context) => $context->value, $user->operatesIn),
                JSON_THROW_ON_ERROR
            ),
            'active'      => $user->active,
            'verified_at' => $user->email->verifiedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
