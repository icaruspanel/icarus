<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserHydrator;
use Icarus\Domain\User\UserId;
use Icarus\Domain\User\UserRepository;
use Icarus\Infrastructure\Shared\DispatchesAggregateEvents;
use Icarus\Infrastructure\Shared\HandlesIlluminateConnections;
use Icarus\Infrastructure\Shared\IdentityMap;
use Icarus\Infrastructure\Shared\SnapshotMap;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

/**
 * @phpstan-import-type UserData from \Icarus\Domain\User\UserHydrator
 */
final class IlluminateUserRepository implements UserRepository
{
    use HandlesIlluminateConnections,
        DispatchesAggregateEvents;

    private const string TABLE = 'users';

    private const array FIELDS = ['id', 'name', 'email', 'password', 'verified_at'];

    /**
     * @var \Icarus\Domain\User\UserHydrator
     */
    private UserHydrator $hydrator;

    /**
     * @var \Icarus\Infrastructure\Shared\IdentityMap
     */
    private IdentityMap $identityMap;

    /**
     * @var \Icarus\Infrastructure\Shared\SnapshotMap
     */
    private SnapshotMap $snapshotMap;

    public function __construct(
        UserHydrator        $hydrator,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        Dispatcher          $dispatcher
    )
    {
        $this->hydrator    = $hydrator;
        $this->identityMap = $identityMap;
        $this->snapshotMap = $snapshotMap;

        $this->setConnection($connection);
        $this->setDispatcher($dispatcher);
    }

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
    public function save(User $user): bool
    {
        $raw = $this->hydrator->dehydrate($user);

        // Check if there's a snapshot.
        if ($this->snapshotMap->has($user->id, User::class)) {
            // If there is a snapshot, we're dealing with an update, so we
            // can save that.
            $fields = $this->snapshotMap->toPersist($user->id, User::class, $raw);

            if (empty($fields)) {
                return true;
            }

            // Make sure we set the updated_at field.
            $fields['updated_at'] = CarbonImmutable::now();

            $success = $this->query()
                            ->from(self::TABLE)
                            ->where('id', $user->id)
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
            // Like to record an updated snapshot.
            $this->snapshotMap->put($user->id, User::class, $raw);

            // And dispatch any necessary events.
            $this->dispatchEvents($user->releaseEvents());

            return true;
        }

        return false;
    }

    /**
     * Find a user by its ID.
     *
     * @param \Icarus\Domain\User\UserId $id
     *
     * @return \Icarus\Domain\User\User|null
     */
    public function find(UserId $id): ?User
    {
        // Short-circuit and use the object from the identity map if it exists.
        if ($this->identityMap->has($id, User::class)) {
            return $this->identityMap->get($id, User::class);
        }

        $results = $this->query()
                        ->select(self::FIELDS)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        $results = (array)$results;

        /**
         * This is here to appease the PHPStan gods.
         *
         * @var UserData $results
         */

        // Hydrate the user object.
        $user = $this->hydrator->hydrate($results);

        // Make sure it's stored in the identity map.
        $this->identityMap->put($user->id, $user);

        // Make sure we store a snapshot too.
        $this->snapshotMap->put($user->id, User::class, $results);

        return $user;
    }
}
