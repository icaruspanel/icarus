<?php

namespace App\Providers;

use App\Auth\TokenGuard;
use App\Enum\UserType;
use App\Support\StateManager;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDevelopmentProvider();
        $this->setDateToBeImmutableByDefault();
        $this->registerAuth();
        $this->registerStateManager();
    }

    private function registerDevelopmentProvider(): void
    {
        if ($this->app->environment('production', 'staging') === false) {
            $this->app->register(DevServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function setDateToBeImmutableByDefault(): void
    {
        // The data facade is never used anywhere, this is mostly to get the IDE
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
        return function (AuthManager $auth) {
            $auth->extend('token', function (Application $app, string $name, array $config) use ($auth) {
                /**
                 * @var non-empty-string              $name
                 * @var array{provider?: string|null} $config
                 */
                $provider = $this->getUserProvider($auth, $config);

                $guard = new TokenGuard(
                    $name,
                    $app->make(Request::class),
                    $provider
                );

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
            });
        };
    }

    /**
     * @param array{provider?: string|null} $config
     */
    private function getUserProvider(AuthManager $auth, array $config): UserProvider
    {
        $provider = $auth->createUserProvider($config['provider'] ?? null);

        if ($provider === null) {
            throw new InvalidArgumentException('Unable to retrieve user provider.');
        }

        return $provider;
    }

    private function registerStateManager(): void
    {
        $this->app->singleton(StateManager::class);

        // Register the user type enum as a singleton so that it returns
        // only the current user type state
        $this->app->bind(UserType::class, function (Application $app) {
            return $app->make(StateManager::class)->getState(UserType::class);
        });
    }
}
