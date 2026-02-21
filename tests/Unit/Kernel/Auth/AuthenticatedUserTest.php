<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel\Auth;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\DataObjects\UserResult;
use Icarus\Domain\User\UserId;
use Icarus\Kernel\Auth\AuthenticatedUser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('kernel'), Group('auth')]
class AuthenticatedUserTest extends TestCase
{
    private AuthContext $authContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authContext = new AuthContext(
            UserId::generate(),
            AuthTokenId::generate(),
            OperatingContext::Account,
        );
    }

    private function makeUser(): AuthenticatedUser
    {
        return new AuthenticatedUser(
            $this->authContext,
            'Test User',
            'test@example.com',
        );
    }

    // ——————————————————————————————————————————————
    // create
    // ——————————————————————————————————————————————

    #[Test]
    public function createBuildsFromUserResultAndAuthContext(): void
    {
        $result = new UserResult(
            $this->authContext->userId,
            'Test User',
            'test@example.com',
            CarbonImmutable::now(),
        );

        $user = AuthenticatedUser::create($result, $this->authContext);

        $this->assertSame($this->authContext, $user->authContext);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    // ——————————————————————————————————————————————
    // Authenticatable interface
    // ——————————————————————————————————————————————

    #[Test]
    public function getAuthIdentifierReturnsUserId(): void
    {
        $user = $this->makeUser();

        $this->assertSame($this->authContext->userId->id, $user->getAuthIdentifier());
    }

    #[Test]
    public function getAuthIdentifierNameReturnsId(): void
    {
        $user = $this->makeUser();

        $this->assertSame('id', $user->getAuthIdentifierName());
    }

    #[Test]
    public function getAuthPasswordNameReturnsPassword(): void
    {
        $user = $this->makeUser();

        $this->assertSame('password', $user->getAuthPasswordName());
    }

    #[Test]
    public function getAuthPasswordReturnsEmptyString(): void
    {
        $user = $this->makeUser();

        $this->assertSame('', $user->getAuthPassword());
    }

    #[Test]
    public function getRememberTokenReturnsEmptyString(): void
    {
        $user = $this->makeUser();

        $this->assertSame('', $user->getRememberToken());
    }

    #[Test]
    public function setRememberTokenIsNoOp(): void
    {
        $user = $this->makeUser();

        $user->setRememberToken('some-token');

        $this->assertSame('', $user->getRememberToken());
    }

    #[Test]
    public function getRememberTokenNameReturnsEmptyString(): void
    {
        $user = $this->makeUser();

        $this->assertSame('', $user->getRememberTokenName());
    }
}
