<?php
declare(strict_types=1);

namespace Icarus\Kernel\User;

use Icarus\Domain\User\User;
use Icarus\Domain\User\UserHydrator;
use Icarus\Domain\User\UserId;
use Icarus\Domain\User\UserRepository;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Contracts\RecordsLifecycleEvents;
use Icarus\Kernel\IdentityMap;
use Icarus\Kernel\Persistence\IlluminateBaseRepository;
use Icarus\Kernel\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use stdClass;

/**
 * @phpstan-import-type UserData from \Icarus\Domain\User\UserHydrator
 *
 * @extends \Icarus\Kernel\Persistence\IlluminateBaseRepository<\Icarus\Domain\User\User>
 */
final class IlluminateUserRepository extends IlluminateBaseRepository implements UserRepository, RecordsLifecycleEvents
{
    public const string TABLE = 'users';

    public const array FIELDS = [
        'id',
        'name',
        'email',
        'password',
        'active',
        'operates_in',
        'verified_at'
    ];

    /**
     * @var \Icarus\Domain\User\UserHydrator
     */
    private UserHydrator $hydrator;

    public function __construct(
        UserHydrator        $hydrator,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        EventDispatcher     $dispatcher
    )
    {
        $this->hydrator = $hydrator;

        parent::__construct(
            User::class,
            $connection,
            $identityMap,
            $snapshotMap,
            $dispatcher
        );
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

        // Hydrate a fresh user object.
        $user = $this->hydrator->hydrate($results);

        // Make sure a snapshot is stored too.
        $this->storeSnapshot($user->id, $results);

        // And store the user in the identity map.
        $this->storeIdentity($user->id, $user);

        return $user;
    }

    /**
     * @param object       $aggregate
     *
     * @phpstan-param User $aggregate
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    protected function dehydrate(object $aggregate): array
    {
        return $this->hydrator->dehydrate($aggregate);
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
        return $this->shouldCreate($user->id)
            ? $this->create($user->id, $user, self::TABLE)
            : $this->update($user->id, $user, self::TABLE);
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

        // Return an existing user from the identity map if it exists,
        // otherwise hydrate a new user object.
        return $this->identityMap->get($id, User::class)
               ?? $this->hydrate($results);
    }
}
