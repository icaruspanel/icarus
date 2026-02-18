<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\AuthToken;

use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\AuthToken\TokenPrefix;
use Icarus\Domain\Shared\OperatingContext;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('auth-token')]
class StoredTokenTest extends TestCase
{
    /**
     * @return array<string, array<OperatingContext>>
     */
    public static function operatingContextProvider(): array
    {
        return [
            'Account context'  => [OperatingContext::Account],
            'Platform context' => [OperatingContext::Platform],
        ];
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function createReturnsUnhashedTokenWithCorrectPrefixForContext(OperatingContext $context): void
    {
        $token = StoredToken::create($context);

        $this->assertEquals($context, TokenPrefix::resolve($token->unhashedToken));
        $this->assertTrue(str_starts_with($token->unhashedToken, TokenPrefix::for($context)));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function createReturnsUnhashedTokenOfExpectedLengthForContext(OperatingContext $context): void
    {
        $token = StoredToken::create($context);

        $this->assertEquals($context, TokenPrefix::resolve($token->unhashedToken));
        $this->assertSame(71, strlen($token->unhashedToken));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function createStoresSelectorAsFirst8Characters(OperatingContext $context): void
    {
        $token         = StoredToken::create($context);
        $strippedToken = TokenPrefix::strip($token->unhashedToken);

        $this->assertEquals($context, TokenPrefix::resolve($token->unhashedToken));
        $this->assertIsString($strippedToken);
        $this->assertSame(substr($strippedToken, 0, 8), $token->token->selector);
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function createStoresSecretAsSha256OfRemainingCharacters(OperatingContext $context): void
    {
        $token         = StoredToken::create($context);
        $strippedToken = TokenPrefix::strip($token->unhashedToken);

        $this->assertEquals($context, TokenPrefix::resolve($token->unhashedToken));
        $this->assertIsString($strippedToken);
        $this->assertTrue(hash_equals($token->token->secret, hash('sha256', substr($strippedToken, 8))));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function verifyReturnsTrueForMatchingSelectorAndSecret(OperatingContext $context): void
    {
        $token         = StoredToken::create($context);
        $strippedToken = TokenPrefix::strip($token->unhashedToken);

        $this->assertIsString($strippedToken);

        $selector = substr($strippedToken, 0, 8);
        $secret   = substr($strippedToken, 8);

        $this->assertTrue($token->token->verify($selector, $secret));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function verifyReturnsFalseForWrongSelector(OperatingContext $context): void
    {
        $token         = StoredToken::create($context);
        $strippedToken = TokenPrefix::strip($token->unhashedToken);

        $this->assertIsString($strippedToken);

        $secret = substr($strippedToken, 8);

        $this->assertTrue(hash_equals($token->token->secret, hash('sha256', $secret)));
        $this->assertFalse($token->token->verify(Str::random(8), $secret));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function verifyReturnsFalseForWrongSecret(OperatingContext $context): void
    {
        $token         = StoredToken::create($context);
        $strippedToken = TokenPrefix::strip($token->unhashedToken);

        $this->assertIsString($strippedToken);

        $selector = substr($strippedToken, 0, 8);

        $this->assertSame($selector, $token->token->selector);
        $this->assertFalse($token->token->verify($selector, Str::random(56)));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function verifyReturnsFalseForWrongSelectorAndSecret(OperatingContext $context): void
    {
        $token = StoredToken::create($context);

        $selector = Str::random(8);
        $secret   = Str::random(56);

        $this->assertNotSame($selector, $token->token->selector);
        $this->assertFalse(hash_equals($token->token->secret, hash('sha256', $secret)));
        $this->assertFalse($token->token->verify($selector, $secret));
    }
}
