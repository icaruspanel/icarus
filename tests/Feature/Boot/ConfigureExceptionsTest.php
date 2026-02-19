<?php
declare(strict_types=1);

namespace Tests\Feature\Boot;

use App\Http\Exceptions\NotAuthenticated;
use App\Http\Exceptions\OutOfOperatingContext;
use Icarus\Domain\AuthToken\Exceptions\AuthenticationFailed;
use Icarus\Domain\AuthToken\Exceptions\InvalidCredentials;
use Icarus\Domain\AuthToken\Exceptions\UnableToAuthenticate;
use Icarus\Domain\User\UserId;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('feature'), Group('boot'), Group('exceptions')]
class ConfigureExceptionsTest extends TestCase
{
    /**
     * Register a temporary route that throws the given exception.
     */
    private function registerThrowingRoute(string $uri, \Throwable $exception): void
    {
        $this->app->make(Router::class)
                   ->get($uri, fn () => throw $exception)
                   ->middleware([]);
    }

    // ——————————————————————————————————————————————
    // NotAuthenticated
    // ——————————————————————————————————————————————

    #[Test]
    public function notAuthenticatedRendersAs401(): void
    {
        $this->registerThrowingRoute(
            '/_test/not-authenticated',
            new NotAuthenticated('Token missing'),
        );

        $response = $this->getJson('/_test/not-authenticated');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'message' => 'You are not authenticated',
            ],
        ]);
    }

    // ——————————————————————————————————————————————
    // OutOfOperatingContext
    // ——————————————————————————————————————————————

    #[Test]
    public function outOfOperatingContextRendersAs400(): void
    {
        $this->registerThrowingRoute(
            '/_test/out-of-context',
            new OutOfOperatingContext('Operating context missing'),
        );

        $response = $this->getJson('/_test/out-of-context');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => [
                'message' => 'Operating context missing',
            ],
        ]);
    }

    // ——————————————————————————————————————————————
    // InvalidCredentials
    // ——————————————————————————————————————————————

    #[Test]
    public function invalidCredentialsRendersAs401(): void
    {
        $this->registerThrowingRoute(
            '/_test/invalid-credentials',
            InvalidCredentials::make(),
        );

        $response = $this->getJson('/_test/invalid-credentials');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'message' => 'Invalid credentials',
            ],
        ]);
    }

    // ——————————————————————————————————————————————
    // UnableToAuthenticate
    // ——————————————————————————————————————————————

    #[Test]
    public function unableToAuthenticateRendersAs401WithDetails(): void
    {
        $this->registerThrowingRoute(
            '/_test/unable-to-authenticate',
            UnableToAuthenticate::make('Token save failed', UserId::generate()),
        );

        $response = $this->getJson('/_test/unable-to-authenticate');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'message' => 'Unable to authenticate',
            ],
        ]);
        $response->assertJsonStructure([
            'error' => ['message', 'details'],
        ]);
    }

    // ——————————————————————————————————————————————
    // Base AuthenticationFailed (not a subclass)
    // ——————————————————————————————————————————————

    #[Test]
    public function baseAuthenticationFailedRendersAs500(): void
    {
        // Create a concrete instance of the abstract class via anonymous class
        $exception = new class('Something went wrong') extends AuthenticationFailed {};

        $this->registerThrowingRoute('/_test/auth-failed', $exception);

        $response = $this->getJson('/_test/auth-failed');

        $response->assertStatus(500);
        $response->assertJson([
            'error' => [
                'message' => 'Unable to authenticate',
            ],
        ]);
    }
}
