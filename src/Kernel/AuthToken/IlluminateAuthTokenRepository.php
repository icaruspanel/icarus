<?php
declare(strict_types=1);

namespace Icarus\Kernel\AuthToken;

use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenHydrator;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Contracts\RecordsLifecycleEvents;
use Icarus\Kernel\IdentityMap;
use Icarus\Kernel\Persistence\IlluminateBaseRepository;
use Icarus\Kernel\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use stdClass;

/**
 * @phpstan-import-type AuthTokenData from \Icarus\Domain\AuthToken\AuthTokenHydrator
 *
 * @extends \Icarus\Kernel\Persistence\IlluminateBaseRepository<\Icarus\Domain\AuthToken\AuthToken>
 */
final class IlluminateAuthTokenRepository extends IlluminateBaseRepository implements AuthTokenRepository, RecordsLifecycleEvents
{
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
     * @param \Icarus\Domain\AuthToken\AuthTokenHydrator $hydrator
     * @param \Illuminate\Database\ConnectionInterface   $connection
     * @param \Icarus\Kernel\IdentityMap                 $identityMap
     * @param \Icarus\Kernel\SnapshotMap                 $snapshotMap
     * @param \Icarus\Kernel\Contracts\EventDispatcher   $dispatcher
     */
    public function __construct(
        AuthTokenHydrator   $hydrator,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        EventDispatcher     $dispatcher
    )
    {
        $this->hydrator = $hydrator;

        parent::__construct(
            AuthToken::class,
            $connection,
            $identityMap,
            $snapshotMap,
            $dispatcher
        );
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

        // Make sure a snapshot is stored too.
        $this->storeSnapshot($authToken->id, $results);

        // And store the user in the identity map.
        $this->storeIdentity($authToken->id, $authToken);

        return $authToken;
    }

    /**
     * @param object                                     $aggregate
     *
     * @phpstan-param \Icarus\Domain\AuthToken\AuthToken $aggregate
     *
     * @return array<string, mixed>
     */
    protected function dehydrate(object $aggregate): array
    {
        return $this->hydrator->dehydrate($aggregate);
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
        return $this->shouldCreate($token->id)
            ? $this->create($token->id, $token, self::TABLE)
            : $this->update($token->id, $token, self::TABLE);
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

        // Return an existing auth-token from the identity map if it exists,
        // otherwise hydrate a new auth-token object.
        return $this->identityMap->get($id, AuthToken::class)
               ?? $this->hydrate($results);
    }
}
