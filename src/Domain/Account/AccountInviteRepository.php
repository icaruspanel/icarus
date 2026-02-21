<?php

namespace Icarus\Domain\Account;

interface AccountInviteRepository
{
    /**
     * Save the account invite.
     *
     * @param \Icarus\Domain\Account\AccountInvite $accountInvite
     *
     * @return bool
     */
    public function save(AccountInvite $accountInvite): bool;

    /**
     * Find an account invite by its ID.
     *
     * @param \Icarus\Domain\Account\AccountInviteId $accountInviteId
     *
     * @return \Icarus\Domain\Account\AccountInvite|null
     */
    public function find(AccountInviteId $accountInviteId): ?AccountInvite;

    /**
     * Find an account invite by its code.
     *
     * @param string $code
     *
     * @return \Icarus\Domain\Account\AccountInvite|null
     */
    public function findByCode(string $code): ?AccountInvite;
}
