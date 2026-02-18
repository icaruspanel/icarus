<?php

namespace Icarus\Domain\User;

interface UserRepository
{
    /**
     * Save the user.
     *
     * Returns <code>true</code> if the user was saved successfully,
     * <code>false</code> otherwise.
     *
     * @param \Icarus\Domain\User\User $user
     *
     * @return bool
     */
    public function save(User $user): bool;

    /**
     * Find a user by its ID.
     *
     * @param \Icarus\Domain\User\UserId $id
     *
     * @return \Icarus\Domain\User\User|null
     */
    public function find(UserId $id): ?User;

    /**
     * Find a user by its email address.
     *
     * @param string $email
     *
     * @return \Icarus\Domain\User\User|null
     */
    public function findByEmail(string $email): ?User;
}
