<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken;

use Icarus\Domain\Shared\OperatingContext;
use SensitiveParameter;

final class TokenPrefix
{
    /**
     * @var array<string, string>
     */
    private static array $prefixes = [
        'account'  => 'ic_acc_',
        'platform' => 'ic_pla_',
    ];

    public static function for(OperatingContext $context): string
    {
        return self::$prefixes[$context->value];
    }

    public static function resolve(#[SensitiveParameter] string $token): ?OperatingContext
    {
        return array_find(
            OperatingContext::cases(),
            fn (OperatingContext $context) => str_starts_with($token, self::for($context))
        );
    }

    public static function strip(#[SensitiveParameter] string $token): ?string
    {
        $context = self::resolve($token);

        return $context ? substr($token, strlen(self::for($context))) : null;

    }
}
