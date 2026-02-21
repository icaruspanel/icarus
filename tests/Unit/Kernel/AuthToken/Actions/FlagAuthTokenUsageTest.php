<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel\AuthToken\Actions;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use Icarus\Kernel\AuthToken\Actions\FlagAuthTokenUsage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('kernel'), Group('auth-token')]
class FlagAuthTokenUsageTest extends TestCase
{
    private AuthTokenRepository&MockInterface $authTokenRepository;

    private FlagAuthTokenUsage $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authTokenRepository = Mockery::mock(AuthTokenRepository::class);
        $this->handler             = new FlagAuthTokenUsage($this->authTokenRepository);
    }

    #[Test]
    public function executeUpdatesLastUsedAtAndSaves(): void
    {
        $now         = CarbonImmutable::now();
        $authTokenId = AuthTokenId::generate();
        $context     = OperatingContext::Account;

        $authToken = new AuthToken(
            $authTokenId,
            StoredToken::create($context)->token,
            UserId::generate(),
            $context,
        );

        $this->authTokenRepository->shouldReceive('find')
                                  ->with($authTokenId)
                                  ->once()
                                  ->andReturn($authToken);

        $this->authTokenRepository->shouldReceive('save')
                                  ->with($authToken)
                                  ->once()
                                  ->andReturnTrue();

        $this->handler->execute($authTokenId, $now);

        $this->assertNotNull($authToken->lastUsedAt);
        $this->assertTrue($authToken->lastUsedAt->equalTo($now));
    }

    #[Test]
    public function executeDoesNothingWhenTokenNotFound(): void
    {
        $now         = CarbonImmutable::now();
        $authTokenId = AuthTokenId::generate();

        $this->authTokenRepository->shouldReceive('find')
                                  ->with($authTokenId)
                                  ->once()
                                  ->andReturnNull();

        $this->authTokenRepository->shouldNotReceive('save');

        $this->handler->execute($authTokenId, $now);
    }
}
