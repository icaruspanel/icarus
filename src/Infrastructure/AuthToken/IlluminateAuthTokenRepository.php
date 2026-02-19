<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\AuthToken;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenHydrator;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Infrastructure\Shared\HandlesIlluminateConnections;
use Icarus\Infrastructure\Shared\IdentityMap;
use Icarus\Infrastructure\Shared\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use stdClass;

/**
 * @phpstan-import-type AuthTokenData from \Icarus\Domain\AuthToken\AuthTokenHydrator
 */
final class IlluminateAuthTokenRepository implements AuthTokenRepository
{
    use HandlesIlluminateConnections;

    /**
     *
     */
    public const string TABLE = 'auth_tokens';

    /**
     *
     */
    public const array FIELDS = ['id', 'user_id', 'selector', 'secret', 'context', 'user_agent', 'ip', 'last_used_at', 'expires_at', 'revoked_at', 'revoked_reason'];

    /**
     * @var \Icarus\Domain\AuthToken\AuthTokenHydrator
     */
    private AuthTokenHydrator $hydrator;

    /**
     * @var \Icarus\Infrastructure\Shared\IdentityMap
     */
    private IdentityMap $identityMap;

    /**
     * @var \Icarus\Infrastructure\Shared\SnapshotMap
     */
    private SnapshotMap $snapshotMap;

    /**
     * @var \Icarus\Domain\Shared\EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @param \Icarus\Domain\AuthToken\AuthTokenHydrator $hydrator
     * @param \Illuminate\Database\ConnectionInterface   $connection
     * @param \Icarus\Infrastructure\Shared\IdentityMap  $identityMap
     * @param \Icarus\Infrastructure\Shared\SnapshotMap  $snapshotMap
     * @param \Icarus\Domain\Shared\EventDispatcher      $dispatcher
     */
    public function __construct(
        AuthTokenHydrator   $hydrator,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        EventDispatcher     $dispatcher
    )
    {
        $this->hydrator    = $hydrator;
        $this->identityMap = $identityMap;
        $this->snapshotMap = $snapshotMap;
        $this->dispatcher  = $dispatcher;

        $this->setConnection($connection);
    }

    /**
     * @param array<string, mixed>|\stdClass $results
     *
     * @phpstan-param AuthTokenData|stdClass $results
     *
     * @return \Icarus\Domain\AuthToken\AuthToken
     */
    protected function hydrate(array|stdClass $results): AuthToken
    {
        $results = (array)$results;

        /**
         * @var AuthTokenData $results
         */

        // Hydrate the auth token object.
        $authToken = $this->hydrator->hydrate($results);

        // Make sure it's stored in the identity map.
        $this->identityMap->put($authToken->id, $authToken);

        // Make sure we store a snapshot too.
        $this->snapshotMap->put($authToken->id, AuthToken::class, $results);

        return $authToken;
    }

    /**
     * Save the auth token.
     *
     * @param \Icarus\Domain\AuthToken\AuthToken $token
     *
     * @return bool
     */
    public function save(AuthToken $token): bool
    {
        $raw = $this->hydrator->dehydrate($token);

        // Check if there's a snapshot.
        if ($this->snapshotMap->has($token->id, AuthToken::class)) {
            // If there is a snapshot, we're dealing with an update, so we
            // can save that.
            $fields = $this->snapshotMap->toPersist($token->id, AuthToken::class, $raw);

            if (empty($fields)) {
                return true;
            }

            // Make sure we set the updated_at field.
            $fields['updated_at'] = CarbonImmutable::now();

            $success = $this->query()
                            ->from(self::TABLE)
                            ->where('id', $token->id)
                            ->update($fields) > 0;

        } else {
            $fields = $raw;

            // If there's no snapshot, we're dealing with an insert, so do that.
            $fields['created_at'] = $fields['updated_at'] = CarbonImmutable::now();

            $success = $this->query()
                            ->from(self::TABLE)
                            ->insert($fields);
        }

        // If it was successful, we have things to do.
        if ($success) {
            // Make sure the token is in the identity map.
            $this->identityMap->put($token->id, $token);

            // Like to record an updated snapshot.
            $this->snapshotMap->put($token->id, AuthToken::class, $raw);

            // And dispatch any necessary events.
            $this->dispatcher->dispatchFrom($token);

            return true;
        }

        return false;
    }

    /**
     * Find an auth token by its ID.
     *
     * @param \Icarus\Domain\AuthToken\AuthTokenId $id
     *
     * @return \Icarus\Domain\AuthToken\AuthToken|null
     */
    public function find(AuthTokenId $id): ?AuthToken
    {
        // Short-circuit and use the object from the identity map if it exists.
        if ($this->identityMap->has($id, AuthToken::class)) {
            return $this->identityMap->get($id, AuthToken::class);
        }

        $results = $this->query()
                        ->select(self::FIELDS)
                        ->where('id', $id)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        return $this->hydrate($results);
    }

    /**
     * Find an auth token by its selector.
     *
     * @param string $selector
     *
     * @return \Icarus\Domain\AuthToken\AuthToken|null
     */
    public function findBySelector(string $selector): ?AuthToken
    {
        $results = $this->query()
                        ->select(self::FIELDS)
                        ->where('selector', '=', $selector)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        $results = (array)$results;

        /** @var AuthTokenData $results */

        $id = new AuthTokenId($results['id']);

        // Check if the auth token already exists in the identity map.
        if ($this->identityMap->has($id, AuthToken::class)) {
            return $this->identityMap->get($id, AuthToken::class);
        }

        // Otherwise, hydrate the auth token and return it.
        return $this->hydrate($results);
    }
}
