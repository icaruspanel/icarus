<?php
declare(strict_types=1);

namespace Tests\Feature\Boot;

use App\Http\Middleware\SetAuthContextFromHeaderToken;
use App\Http\Middleware\SetOperatingContext;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('feature'), Group('boot'), Group('middleware')]
class ConfigureMiddlewareTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = $this->app->make(Kernel::class);
    }

    // ——————————————————————————————————————————————
    // aliases
    // ——————————————————————————————————————————————

    #[Test]
    public function contextAuthAliasResolvesToSetAuthContextFromHeaderToken(): void
    {
        $aliases = $this->kernel->getRouteMiddleware();

        $this->assertArrayHasKey('context.auth', $aliases);
        $this->assertSame(SetAuthContextFromHeaderToken::class, $aliases['context.auth']);
    }

    #[Test]
    public function contextOperatingAliasResolvesToSetOperatingContext(): void
    {
        $aliases = $this->kernel->getRouteMiddleware();

        $this->assertArrayHasKey('context.operating', $aliases);
        $this->assertSame(SetOperatingContext::class, $aliases['context.operating']);
    }

    // ——————————————————————————————————————————————
    // account group
    // ——————————————————————————————————————————————

    #[Test]
    public function accountGroupContainsSubstituteBindings(): void
    {
        $groups = $this->kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('account', $groups);
        $this->assertContains(SubstituteBindings::class, $groups['account']);
    }

    #[Test]
    public function accountGroupContainsOperatingContextMiddleware(): void
    {
        $groups = $this->kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('account', $groups);
        $this->assertContains('context.operating:account', $groups['account']);
    }

    // ——————————————————————————————————————————————
    // platform group
    // ——————————————————————————————————————————————

    #[Test]
    public function platformGroupContainsSubstituteBindings(): void
    {
        $groups = $this->kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('platform', $groups);
        $this->assertContains(SubstituteBindings::class, $groups['platform']);
    }

    #[Test]
    public function platformGroupContainsOperatingContextMiddleware(): void
    {
        $groups = $this->kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('platform', $groups);
        $this->assertContains('context.operating:platform', $groups['platform']);
    }
}
