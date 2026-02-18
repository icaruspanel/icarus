<?php

namespace App\Providers;

use App\Auth\AuthTokenGuard;
use App\Icarus;
use Carbon\CarbonImmutable;
use Closure;
use Icarus\Domain\AuthToken\Commands\FlagAuthTokenUsageHandler;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Infrastructure\User\Queries\GetUserById;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDevelopmentProvider();
        $this->registerIcarus();
        $this->setDateToBeImmutableByDefault();
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

    private function setDateToBeImmutableByDefault(): void
    {
        // The Date facade is never used anywhere, this is mostly to get the IDE
        // helper to use 'CarbonImmutable' in the model helpers
        Date::use(CarbonImmutable::class);
    }

    private function registerAuth(): void
    {
        $creator = $this->getAuthGuardCreator();

        if ($this->app->resolved('auth')) {
            $this->app->call($creator);
        } else {
            $this->app->afterResolving('auth', $creator);
        }
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
