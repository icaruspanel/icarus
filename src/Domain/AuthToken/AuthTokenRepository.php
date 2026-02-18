<?php

namespace Icarus\Domain\AuthToken;

interface AuthTokenRepository
{
    /**
     * Save the auth token.
     *
     * @param \Icarus\Domain\AuthToken\AuthToken $token
     *
     * @return bool
     */
    public function save(AuthToken $token): bool;

    /**
     * Find an auth token by its ID.
     *
     * @param \Icarus\Domain\AuthToken\AuthTokenId $id
     *
     * @return \Icarus\Domain\AuthToken\AuthToken|null
     */
    public function find(AuthTokenId $id): ?AuthToken;

    /**
     * Find an auth token by its selector.
     *
     * @param string $selector
     *
     * @return \Icarus\Domain\AuthToken\AuthToken|null
     */
    public function findBySelector(string $selector): ?AuthToken;
}
