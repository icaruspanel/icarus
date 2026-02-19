<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserHydrator;
use Icarus\Domain\User\UserId;
use Icarus\Domain\User\UserRepository;
use Icarus\Infrastructure\Shared\HandlesIlluminateConnections;
use Icarus\Infrastructure\Shared\IdentityMap;
use Icarus\Infrastructure\Shared\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use stdClass;

/**
 * @phpstan-import-type UserData from \Icarus\Domain\User\UserHydrator
 */
final class IlluminateUserRepository implements UserRepository
{
    use HandlesIlluminateConnections;

    public const string TABLE = 'users';

    public const array FIELDS = ['id', 'name', 'email', 'password', 'active', 'verified_at'];

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

    /**
     * @var \Icarus\Domain\Shared\EventDispatcher
     */
    private EventDispatcher $dispatcher;

    public function __construct(
        UserHydrator        $hydrator,
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
     * @phpstan-param UserData|stdClass      $results
     *
     * @return \Icarus\Domain\User\User
     */
    protected function hydrate(array|stdClass $results): User
    {
        $results = (array)$results;

        /**
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
            // Make sure the user is in the identity map.
            $this->identityMap->put($user->id, $user);

            // Like to record an updated snapshot.
            $this->snapshotMap->put($user->id, User::class, $raw);

            // And dispatch any necessary events.
            $this->dispatcher->dispatchFrom($user);

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
                        ->where('id', $id)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        return $this->hydrate($results);
    }

    /**
     * Find a user by its email address.
     *
     * @param string $email
     *
     * @return \Icarus\Domain\User\User|null
     */
    public function findByEmail(string $email): ?User
    {
        $results = $this->query()
                        ->select(self::FIELDS)
                        ->where('email', '=', $email)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        $results = (array)$results;

        /** @var UserData $results */

        $id = new UserId($results['id']);

        // Check if the user already exists in the identity map.
        if ($this->identityMap->has($id, User::class)) {
            return $this->identityMap->get($id, User::class);
        }

        // Otherwise, hydrate the user and return it.
        return $this->hydrate($results);
    }
}
