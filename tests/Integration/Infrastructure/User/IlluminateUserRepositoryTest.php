<?php
declare(strict_types=1);

namespace Tests\Integration\Infrastructure\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\User\HashedPassword;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserEmail;
use Icarus\Domain\User\UserHydrator;
use Icarus\Domain\User\UserId;
use Icarus\Infrastructure\Shared\IdentityMap;
use Icarus\Infrastructure\Shared\SnapshotMap;
use Icarus\Infrastructure\User\IlluminateUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('integration'), Group('core'), Group('infrastructure'), Group('user')]
class IlluminateUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EventDispatcher&MockInterface $dispatcher;

    private IdentityMap $identityMap;

    private SnapshotMap $snapshotMap;

    private IlluminateUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher  = Mockery::mock(EventDispatcher::class);
        $this->identityMap = new IdentityMap();
        $this->snapshotMap = new SnapshotMap();

        $this->repository = new IlluminateUserRepository(
            new UserHydrator(),
            $this->app->make('db.connection'),
            $this->identityMap,
            $this->snapshotMap,
            $this->dispatcher,
        );
    }

    private function makeUser(
        string $email = 'test@example.com',
        string $password = 'password',
        array  $operatesIn = [],
        bool   $active = true,
    ): User
    {
        return new User(
            UserId::generate(),
            'Test User',
            UserEmail::unverified($email),
            HashedPassword::from($password),
            $operatesIn,
            $active,
        );
    }

    // ——————————————————————————————————————————————
    // save (insert)
    // ——————————————————————————————————————————————

    #[Test]
    public function saveInsertsNewUser(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $result = $this->repository->save($user);

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', [
            'id'    => $user->id->id,
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    #[Test]
    public function saveCreatesSnapshotAfterInsert(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        $this->assertTrue($this->snapshotMap->has($user->id, User::class));
    }

    #[Test]
    public function saveAddsToIdentityMapAfterInsert(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        $this->assertTrue($this->identityMap->has($user->id, User::class));
        $this->assertSame($user, $this->identityMap->get($user->id, User::class));
    }

    #[Test]
    public function saveDispatchesEventsAfterInsert(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with($user)
                         ->once();

        $this->repository->save($user);
    }

    // ——————————————————————————————————————————————
    // save (update)
    // ——————————————————————————————————————————————

    #[Test]
    public function saveUpdatesExistingUser(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->twice();

        $this->repository->save($user);

        $user->changeEmail('new@example.com');

        $result = $this->repository->save($user);

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', [
            'id'    => $user->id->id,
            'email' => 'new@example.com',
        ]);
    }

    #[Test]
    public function saveReturnsTrueWithNoChanges(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        // Second save with no changes — should short-circuit
        $result = $this->repository->save($user);

        $this->assertTrue($result);
    }

    // ——————————————————————————————————————————————
    // save (failure)
    // ——————————————————————————————————————————————

    #[Test]
    public function saveReturnsFalseWhenUpdateAffectsNoRows(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        $user->changeEmail('new@example.com');

        // Delete the row behind the repository's back
        DB::table('users')->where('id', $user->id->id)->delete();

        $result = $this->repository->save($user);

        $this->assertFalse($result);
    }

    #[Test]
    public function saveDoesNotUpdateSnapshotOnFailure(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        $snapshotBefore = $this->snapshotMap->get($user->id, User::class);

        $user->changeEmail('new@example.com');

        // Delete the row behind the repository's back
        DB::table('users')->where('id', $user->id->id)->delete();

        $this->repository->save($user);

        $snapshotAfter = $this->snapshotMap->get($user->id, User::class);

        $this->assertSame($snapshotBefore, $snapshotAfter);
    }

    #[Test]
    public function saveDoesNotDispatchEventsOnFailure(): void
    {
        $user = $this->makeUser();

        // Only the initial insert should dispatch — the failed update should not
        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        $user->changeEmail('new@example.com');

        // Delete the row behind the repository's back
        DB::table('users')->where('id', $user->id->id)->delete();

        $this->repository->save($user);
    }

    // ——————————————————————————————————————————————
    // find
    // ——————————————————————————————————————————————

    #[Test]
    public function findReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->find(UserId::generate());

        $this->assertNull($result);
    }

    #[Test]
    public function findReturnsUserFromDatabase(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        // Fresh repository to bypass identity map
        $freshIdentityMap = new IdentityMap();
        $freshSnapshotMap = new SnapshotMap();
        $freshRepository  = new IlluminateUserRepository(
            new UserHydrator(),
            $this->app->make('db.connection'),
            $freshIdentityMap,
            $freshSnapshotMap,
            $this->dispatcher,
        );

        $found = $freshRepository->find($user->id);

        $this->assertNotNull($found);
        $this->assertSame($user->id->id, $found->id->id);
        $this->assertSame('Test User', $found->name);
        $this->assertSame('test@example.com', $found->email->email);
        $this->assertTrue($found->isActive());

        // Verify the entity was added to the identity map and snapshot map
        $this->assertTrue($freshIdentityMap->has($user->id, User::class));
        $this->assertSame($found, $freshIdentityMap->get($user->id, User::class));
        $this->assertTrue($freshSnapshotMap->has($user->id, User::class));
    }

    #[Test]
    public function findReturnsUserFromIdentityMap(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        // Same repository — identity map should return the same object
        $found = $this->repository->find($user->id);

        $this->assertSame($user, $found);
    }

    // ——————————————————————————————————————————————
    // findByEmail
    // ——————————————————————————————————————————————

    #[Test]
    public function findByEmailReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    #[Test]
    public function findByEmailReturnsUserFromDatabase(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        // Fresh repository to bypass identity map
        $freshIdentityMap = new IdentityMap();
        $freshSnapshotMap = new SnapshotMap();
        $freshRepository  = new IlluminateUserRepository(
            new UserHydrator(),
            $this->app->make('db.connection'),
            $freshIdentityMap,
            $freshSnapshotMap,
            $this->dispatcher,
        );

        $found = $freshRepository->findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertSame($user->id->id, $found->id->id);
        $this->assertSame('test@example.com', $found->email->email);

        // Verify the entity was added to the identity map and snapshot map
        $this->assertTrue($freshIdentityMap->has($user->id, User::class));
        $this->assertSame($found, $freshIdentityMap->get($user->id, User::class));
        $this->assertTrue($freshSnapshotMap->has($user->id, User::class));
    }

    #[Test]
    public function findByEmailReturnsUserFromIdentityMapWhenAlreadyLoaded(): void
    {
        $user = $this->makeUser();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        // findByEmail always queries the DB first, but checks identity map by ID after
        $found = $this->repository->findByEmail('test@example.com');

        $this->assertSame($user, $found);
    }

    // ——————————————————————————————————————————————
    // verified user
    // ——————————————————————————————————————————————

    #[Test]
    public function saveAndFindPreservesVerifiedAt(): void
    {
        $now  = CarbonImmutable::create(1988, 6, 24, 2, 5, 7);
        $user = new User(
            UserId::generate(),
            'Test User',
            UserEmail::verified('test@example.com', $now),
            HashedPassword::from('password'),
        );

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($user);

        $freshRepository = new IlluminateUserRepository(
            new UserHydrator(),
            $this->app->make('db.connection'),
            new IdentityMap(),
            new SnapshotMap(),
            $this->dispatcher,
        );

        $found = $freshRepository->find($user->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->email->verified);
        $this->assertNotNull($found->email->verifiedAt);
        $this->assertSame(1988, $found->email->verifiedAt->year);
    }
}
