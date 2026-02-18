<?php

namespace App\Providers;

use App\Auth\AuthenticatedUser;
use App\Auth\AuthTokenGuard;
use App\Icarus;
use Closure;
use Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsageHandler;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Infrastructure\User\Queries\GetUserById;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDevelopmentProvider();
        $this->registerIcarus();
        $this->registerAuth();
    }

    private function registerDevelopmentProvider(): void
    {
        if ($this->app->environment('production', 'staging') === false) {
            $this->app->register(DevServiceProvider::class);
        }
    }

    private function registerIcarus(): void
    {
        // There's a chance that Icarus could be resolved multiple times, so we
        // ensure that it doesn't infinitely queue up the refresh
        static $resolved = false;

        $this->app->scoped(Icarus::class);
        $this->app->afterResolving(Icarus::class, function (Icarus $icarus) use (&$resolved) {
            if ($resolved === false) {
                $this->app->refresh(AuthContext::class, $icarus, 'setAuthContext');
            }

            $resolved = true;
        });
    }

    private function registerAuth(): void
    {
        // First we bind the custom guard creator
        $creator = $this->getAuthGuardCreator();

        if ($this->app->resolved('auth')) {
            $this->app->call($creator);
        } else {
            $this->app->afterResolving('auth', $creator);
        }

        // Then we add a binding for our custom auth token guard
        $this->app->bind(AuthTokenGuard::class, function (Application $app) {
            $guard = $app->make(AuthManager::class)->guard();

            if (! $guard instanceof AuthTokenGuard) {
                throw new RuntimeException('The guard is not an instance of AuthTokenGuard');
            }

            return $guard;
        });

        // And finally we bind the authenticated user
        $this->app->bind(AuthenticatedUser::class, function (Application $app) {
            return $app->make(AuthTokenGuard::class)->user();
        });
    }

    private function getAuthGuardCreator(): Closure
    {
        return static function (AuthManager $auth) {
            $auth->extend('auth-token', function (Application $app, string $name, array $config) {
                /** @var non-empty-string $name */
                $guard = new AuthTokenGuard(
                    $name,
                    $app->make(GetUserById::class),
                    $app->make(FlagAuthTokenUsageHandler::class),
                    $app->bound(AuthContext::class) ? $app->make(AuthContext::class) : null,
                );

                $app->refresh(AuthContext::class, $guard, 'setAuthContext');

                return $guard;
            });
        };
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
