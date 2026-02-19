<?php
declare(strict_types=1);

namespace Tests\Integration\Infrastructure\AuthToken;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenHydrator;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\HashedPassword;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserEmail;
use Icarus\Domain\User\UserHydrator;
use Icarus\Domain\User\UserId;
use Icarus\Infrastructure\AuthToken\IlluminateAuthTokenRepository;
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

#[Group('integration'), Group('core'), Group('infrastructure'), Group('auth-token')]
class IlluminateAuthTokenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EventDispatcher&MockInterface $dispatcher;

    private IdentityMap $identityMap;

    private SnapshotMap $snapshotMap;

    private IlluminateAuthTokenRepository $repository;

    private UserId $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher  = Mockery::mock(EventDispatcher::class);
        $this->identityMap = new IdentityMap();
        $this->snapshotMap = new SnapshotMap();

        $this->repository = new IlluminateAuthTokenRepository(
            new AuthTokenHydrator(),
            $this->app->make('db.connection'),
            $this->identityMap,
            $this->snapshotMap,
            $this->dispatcher,
        );

        $this->userId = $this->createUser();
    }

    /**
     * Create a user for the foreign key constraint.
     */
    private function createUser(): UserId
    {
        $userDispatcher = Mockery::mock(EventDispatcher::class);
        $userDispatcher->shouldReceive('dispatchFrom')->once();

        $user = new User(
            UserId::generate(),
            'Test User',
            UserEmail::unverified('test@example.com'),
            HashedPassword::from('password'),
        );

        $userRepository = new IlluminateUserRepository(
            new UserHydrator(),
            $this->app->make('db.connection'),
            new IdentityMap(),
            new SnapshotMap(),
            $userDispatcher,
        );

        $userRepository->save($user);

        return $user->id;
    }

    private function makeToken(
        ?UserId          $userId = null,
        OperatingContext  $context = OperatingContext::Account,
        ?Device          $device = null,
        ?CarbonImmutable $expiresAt = null,
    ): AuthToken
    {
        return new AuthToken(
            AuthTokenId::generate(),
            new StoredToken('sel12345', hash('sha256', 'secret')),
            $userId ?? $this->userId,
            $context,
            $device ?? new Device('Mozilla/5.0', '127.0.0.1'),
            null,
            $expiresAt,
        );
    }

    // ——————————————————————————————————————————————
    // save (insert)
    // ——————————————————————————————————————————————

    #[Test]
    public function saveInsertsNewToken(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $result = $this->repository->save($token);

        $this->assertTrue($result);
        $this->assertDatabaseHas('auth_tokens', [
            'id'       => $token->id->id,
            'user_id'  => $this->userId->id,
            'selector' => 'sel12345',
            'context'  => OperatingContext::Account->value,
        ]);
    }

    #[Test]
    public function saveCreatesSnapshotAfterInsert(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $this->assertTrue($this->snapshotMap->has($token->id, AuthToken::class));
    }

    #[Test]
    public function saveAddsToIdentityMapAfterInsert(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $this->assertTrue($this->identityMap->has($token->id, AuthToken::class));
        $this->assertSame($token, $this->identityMap->get($token->id, AuthToken::class));
    }

    #[Test]
    public function saveDispatchesEventsAfterInsert(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')
                         ->with($token)
                         ->once();

        $this->repository->save($token);
    }

    // ——————————————————————————————————————————————
    // save (update)
    // ——————————————————————————————————————————————

    #[Test]
    public function saveUpdatesExistingToken(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->twice();

        $this->repository->save($token);

        $now = CarbonImmutable::now();
        $token->updateUsedAt($now);

        $result = $this->repository->save($token);

        $this->assertTrue($result);
        $this->assertDatabaseHas('auth_tokens', [
            'id'           => $token->id->id,
            'last_used_at' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    #[Test]
    public function saveReturnsTrueWithNoChanges(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        // Second save with no changes — should short-circuit
        $result = $this->repository->save($token);

        $this->assertTrue($result);
    }

    #[Test]
    public function savePersistsRevocation(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->twice();

        $this->repository->save($token);

        $token->revoke('Suspicious activity');

        $result = $this->repository->save($token);

        $this->assertTrue($result);
        $this->assertDatabaseHas('auth_tokens', [
            'id'             => $token->id->id,
            'revoked_reason' => 'Suspicious activity',
        ]);
    }

    // ——————————————————————————————————————————————
    // save (failure)
    // ——————————————————————————————————————————————

    #[Test]
    public function saveReturnsFalseWhenUpdateAffectsNoRows(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $now = CarbonImmutable::now();
        $token->updateUsedAt($now);

        // Delete the row behind the repository's back
        DB::table('auth_tokens')->where('id', $token->id->id)->delete();

        $result = $this->repository->save($token);

        $this->assertFalse($result);
    }

    #[Test]
    public function saveDoesNotUpdateSnapshotOnFailure(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $snapshotBefore = $this->snapshotMap->get($token->id, AuthToken::class);

        $now = CarbonImmutable::now();
        $token->updateUsedAt($now);

        // Delete the row behind the repository's back
        DB::table('auth_tokens')->where('id', $token->id->id)->delete();

        $this->repository->save($token);

        $snapshotAfter = $this->snapshotMap->get($token->id, AuthToken::class);

        $this->assertSame($snapshotBefore, $snapshotAfter);
    }

    #[Test]
    public function saveDoesNotDispatchEventsOnFailure(): void
    {
        $token = $this->makeToken();

        // Only the initial insert should dispatch — the failed update should not
        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $now = CarbonImmutable::now();
        $token->updateUsedAt($now);

        // Delete the row behind the repository's back
        DB::table('auth_tokens')->where('id', $token->id->id)->delete();

        $this->repository->save($token);
    }

    // ——————————————————————————————————————————————
    // find
    // ——————————————————————————————————————————————

    #[Test]
    public function findReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->find(AuthTokenId::generate());

        $this->assertNull($result);
    }

    #[Test]
    public function findReturnsTokenFromDatabase(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        // Fresh repository to bypass identity map
        $freshIdentityMap = new IdentityMap();
        $freshSnapshotMap = new SnapshotMap();
        $freshRepository  = new IlluminateAuthTokenRepository(
            new AuthTokenHydrator(),
            $this->app->make('db.connection'),
            $freshIdentityMap,
            $freshSnapshotMap,
            $this->dispatcher,
        );

        $found = $freshRepository->find($token->id);

        $this->assertNotNull($found);
        $this->assertSame($token->id->id, $found->id->id);
        $this->assertSame($this->userId->id, $found->userId->id);
        $this->assertSame('sel12345', $found->token->selector);
        $this->assertSame(OperatingContext::Account, $found->context);
        $this->assertSame('Mozilla/5.0', $found->device->userAgent);
        $this->assertSame('127.0.0.1', $found->device->ip);

        // Verify the entity was added to the identity map and snapshot map
        $this->assertTrue($freshIdentityMap->has($token->id, AuthToken::class));
        $this->assertSame($found, $freshIdentityMap->get($token->id, AuthToken::class));
        $this->assertTrue($freshSnapshotMap->has($token->id, AuthToken::class));
    }

    #[Test]
    public function findReturnsTokenFromIdentityMap(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        // Same repository — identity map should return the same object
        $found = $this->repository->find($token->id);

        $this->assertSame($token, $found);
    }

    // ——————————————————————————————————————————————
    // findBySelector
    // ——————————————————————————————————————————————

    #[Test]
    public function findBySelectorReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findBySelector('nonexistent');

        $this->assertNull($result);
    }

    #[Test]
    public function findBySelectorReturnsTokenFromDatabase(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        // Fresh repository to bypass identity map
        $freshIdentityMap = new IdentityMap();
        $freshSnapshotMap = new SnapshotMap();
        $freshRepository  = new IlluminateAuthTokenRepository(
            new AuthTokenHydrator(),
            $this->app->make('db.connection'),
            $freshIdentityMap,
            $freshSnapshotMap,
            $this->dispatcher,
        );

        $found = $freshRepository->findBySelector('sel12345');

        $this->assertNotNull($found);
        $this->assertSame($token->id->id, $found->id->id);
        $this->assertSame('sel12345', $found->token->selector);

        // Verify the entity was added to the identity map and snapshot map
        $this->assertTrue($freshIdentityMap->has($token->id, AuthToken::class));
        $this->assertSame($found, $freshIdentityMap->get($token->id, AuthToken::class));
        $this->assertTrue($freshSnapshotMap->has($token->id, AuthToken::class));
    }

    #[Test]
    public function findBySelectorReturnsTokenFromIdentityMapWhenAlreadyLoaded(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        // findBySelector always queries the DB first, but checks identity map by ID after
        $found = $this->repository->findBySelector('sel12345');

        $this->assertSame($token, $found);
    }

    // ——————————————————————————————————————————————
    // timestamps
    // ——————————————————————————————————————————————

    #[Test]
    public function saveAndFindPreservesExpiresAt(): void
    {
        $expiresAt = CarbonImmutable::create(2030, 6, 24, 12, 0, 0);
        $token     = $this->makeToken(expiresAt: $expiresAt);

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $freshRepository = new IlluminateAuthTokenRepository(
            new AuthTokenHydrator(),
            $this->app->make('db.connection'),
            new IdentityMap(),
            new SnapshotMap(),
            $this->dispatcher,
        );

        $found = $freshRepository->find($token->id);

        $this->assertNotNull($found);
        $this->assertNotNull($found->expiresAt);
        $this->assertSame(2030, $found->expiresAt->year);
        $this->assertSame(6, $found->expiresAt->month);
        $this->assertSame(24, $found->expiresAt->day);
    }

    #[Test]
    public function saveAndFindPreservesNullTimestamps(): void
    {
        $token = $this->makeToken();

        $this->dispatcher->shouldReceive('dispatchFrom')->once();

        $this->repository->save($token);

        $freshRepository = new IlluminateAuthTokenRepository(
            new AuthTokenHydrator(),
            $this->app->make('db.connection'),
            new IdentityMap(),
            new SnapshotMap(),
            $this->dispatcher,
        );

        $found = $freshRepository->find($token->id);

        $this->assertNotNull($found);
        $this->assertNull($found->lastUsedAt);
        $this->assertNull($found->expiresAt);
        $this->assertNull($found->revokedAt);
        $this->assertNull($found->revokedReason);
    }
}
