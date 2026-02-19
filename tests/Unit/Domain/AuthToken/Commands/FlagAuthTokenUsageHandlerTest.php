<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken\Commands;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsage;
use Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsageHandler;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('auth-token')]
class FlagAuthTokenUsageHandlerTest extends TestCase
{
    private AuthTokenRepository&MockInterface $authTokenRepository;

    private FlagAuthTokenUsageHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authTokenRepository = Mockery::mock(AuthTokenRepository::class);
        $this->handler             = new FlagAuthTokenUsageHandler($this->authTokenRepository);
    }

    #[Test]
    public function handleUpdatesLastUsedAtAndSaves(): void
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

        $command = new FlagAuthTokenUsage($authTokenId, $now);

        $this->handler->handle($command);

        $this->assertNotNull($authToken->lastUsedAt);
        $this->assertTrue($authToken->lastUsedAt->equalTo($now));
    }

    #[Test]
    public function handleDoesNothingWhenTokenNotFound(): void
    {
        $now         = CarbonImmutable::now();
        $authTokenId = AuthTokenId::generate();

        $this->authTokenRepository->shouldReceive('find')
                                  ->with($authTokenId)
                                  ->once()
                                  ->andReturnNull();

        $this->authTokenRepository->shouldNotReceive('save');

        $command = new FlagAuthTokenUsage($authTokenId, $now);

        $this->handler->handle($command);
    }
}
