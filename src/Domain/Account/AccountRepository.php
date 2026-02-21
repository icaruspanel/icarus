<?php

namespace Icarus\Domain\Account;

interface AccountRepository
{
    /**
     * Saves an account to the repository.
     *
     * @param Account $account The account to save.
     *
     * @return bool True if the account was saved successfully, false otherwise.
     */
    public function save(Account $account): bool;

    /**
     * Finds an account by its ID.
     *
     * @param AccountId $accountId The ID of the account to find.
     *
     * @return Account|null The found account or null if not found.
     */
    public function find(AccountId $accountId): ?Account;
}
