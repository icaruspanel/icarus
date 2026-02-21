<?php
declare(strict_types=1);

namespace Icarus\Kernel\Account;

use Icarus\Domain\Account\Account;
use Icarus\Domain\Account\AccountHydrator;
use Icarus\Domain\Account\AccountId;
use Icarus\Domain\Account\AccountRepository;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Contracts\RecordsLifecycleEvents;
use Icarus\Kernel\IdentityMap;
use Icarus\Kernel\Persistence\IlluminateBaseRepository;
use Icarus\Kernel\SnapshotMap;
use Illuminate\Database\ConnectionInterface;
use stdClass;

/**
 * @phpstan-import-type AccountData from \Icarus\Domain\Account\AccountHydrator
 *
 * @extends \Icarus\Kernel\Persistence\IlluminateBaseRepository<\Icarus\Domain\Account\Account>
 */
final class IlluminateAccountRepository extends IlluminateBaseRepository implements AccountRepository, RecordsLifecycleEvents
{
    public const string TABLE = 'accounts';

    public const array FIELDS = ['id', 'name'];

    private AccountHydrator $hydrator;

    public function __construct(
        AccountHydrator     $hydrator,
        ConnectionInterface $connection,
        IdentityMap         $identityMap,
        SnapshotMap         $snapshotMap,
        EventDispatcher     $dispatcher
    )
    {
        $this->hydrator = $hydrator;

        parent::__construct(Account::class, $connection, $identityMap, $snapshotMap, $dispatcher);
    }

    /**
     * @param array<string, mixed>|\stdClass $results
     *
     * @return object
     *
     * @phpstan-return \Icarus\Domain\Account\Account
     */
    protected function hydrate(array|stdClass $results): object
    {
        $results = (array)$results;

        /**
         * @var AccountData $results
         */

        // Hydrate a fresh object.
        $account = $this->hydrator->hydrate($results);

        // Make sure a snapshot is stored too.
        $this->storeSnapshot($account->id, $results);

        // And store its store in the identity map.
        $this->storeIdentity($account->id, $account);

        return $account;
    }

    /**
     * @param object                                 $aggregate
     *
     * @phpstan-param \Icarus\Domain\Account\Account $aggregate
     *
     * @return array<string, mixed>
     */
    protected function dehydrate(object $aggregate): array
    {
        return $this->hydrator->dehydrate($aggregate);
    }

    /**
     * Saves an account to the repository.
     *
     * @param Account $account The account to save.
     *
     * @return bool True if the account was saved successfully, false otherwise.
     */
    public function save(Account $account): bool
    {
        return $this->shouldCreate($account->id)
            ? $this->create($account->id, $account, self::TABLE)
            : $this->update($account->id, $account, self::TABLE);
    }

    /**
     * Finds an account by its ID.
     *
     * @param AccountId $accountId The ID of the account to find.
     *
     * @return Account|null The found account or null if not found.
     */
    public function find(AccountId $accountId): ?Account
    {
        if ($this->identityMap->has($accountId, Account::class)) {
            return $this->identityMap->get($accountId, Account::class);
        }

        $results = $this->query()
                        ->select(self::FIELDS)
                        ->where('id', $accountId)
                        ->from(self::TABLE)
                        ->first();

        if ($results === null) {
            return null;
        }

        return $this->hydrate($results);
    }
}
