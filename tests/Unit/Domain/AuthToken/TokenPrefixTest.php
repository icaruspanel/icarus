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
class TokenPrefixTest extends TestCase
{
    /**
     * @return array<string, array{0: OperatingContext, 1: string}>
     */
    public static function prefixedOperatingContextProvider(): array
    {
        return [
            'Account context'  => [OperatingContext::Account, 'ic_acc_'],
            'Platform context' => [OperatingContext::Platform, 'ic_pla_'],
        ];
    }

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

    #[Test, DataProvider('prefixedOperatingContextProvider')]
    public function forReturnsCorrectPrefixForOperatingContext(OperatingContext $context, string $prefix): void
    {
        $this->assertSame($prefix, TokenPrefix::for($context));
    }

    #[Test, DataProvider('operatingContextProvider')]
    public function resolveReturnsCorrectContextForOperatingContext(OperatingContext $context): void
    {
        $token = StoredToken::create($context);

        $this->assertSame($context, TokenPrefix::resolve($token->unhashedToken));
    }

    #[Test]
    public function resolveReturnsNullForUnknownPrefix(): void
    {
        $token = Str::random(7);

        $this->assertNull(TokenPrefix::resolve($token));
    }

    #[Test, DataProvider('prefixedOperatingContextProvider')]
    public function stripRemovesPrefixForOperatingContext(OperatingContext $context, string $prefix): void
    {
        $token = $prefix . 'abcde';

        $this->assertSame('abcde', TokenPrefix::strip($token));
    }

    #[Test]
    public function stripReturnsNullForUnknownPrefix(): void
    {
        $token = Str::random(7);

        $this->assertNull(TokenPrefix::strip($token));
    }
}
