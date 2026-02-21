<?php
declare(strict_types=1);

namespace Icarus\Domain\Account;

/**
 * @phpstan-type AccountData array{
 *     id: string,
 *     name: string
 * }
 */
final class AccountHydrator
{
    /**
     * @param array<string, mixed> $results
     *
     * @phpstan-param AccountData  $results
     *
     * @return \Icarus\Domain\Account\Account
     */
    public function hydrate(array $results): Account
    {
        return new Account(
            new AccountId($results['id']),
            $results['name']
        );
    }

    /**
     * @param \Icarus\Domain\Account\Account $account
     *
     * @return array<string, mixed>
     *
     * @phpstan-return AccountData
     */
    public function dehydrate(Account $account): array
    {
        return [
            'id'   => $account->id->id,
            'name' => $account->name,
        ];
    }
}
