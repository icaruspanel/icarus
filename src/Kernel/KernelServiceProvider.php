<?php
declare(strict_types=1);

namespace Icarus\Kernel;

use Closure;
use Icarus\Domain\Shared\AuthContext;
use Icarus\Kernel\Auth\AuthenticatedUser;
use Icarus\Kernel\Auth\AuthTokenGuard;
use Icarus\Kernel\AuthToken\Actions\FlagAuthTokenUsage;
use Icarus\Kernel\Contracts\EventDispatcher;
use Icarus\Kernel\Contracts\Transaction;
use Icarus\Kernel\Modules\Collectors\CollectorRegistry;
use Icarus\Kernel\Modules\Collectors\Routes\RouteCollectorHandler;
use Icarus\Kernel\Modules\Contracts\RouteCollector;
use Icarus\Kernel\Modules\ModuleRegistry;
use Icarus\Kernel\Persistence\IlluminateTransaction;
use Icarus\Kernel\User\Actions\GetUserById;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class KernelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMaps();
        $this->registerPersistence();
        $this->registerEventDispatcher();
        $this->registerIcarus();
        $this->registerAuth();
    }

    private function registerMaps(): void
    {
        // Register the identity map and snapshot map.
        $this->app->singleton(IdentityMap::class);
        $this->app->singleton(SnapshotMap::class);
    }

    private function registerPersistence(): void
    {
        // Register the transaction wrapper.
        $this->app->singleton(Transaction::class, IlluminateTransaction::class);
    }

    private function registerEventDispatcher(): void
    {
        // Register the event dispatcher.
        $this->app->singleton(EventDispatcher::class, IlluminateEventDispatcher::class);
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
                    $app->make(FlagAuthTokenUsage::class),
                    $app->bound(AuthContext::class) ? $app->make(AuthContext::class) : null,
                );

                $app->refresh(AuthContext::class, $guard, 'setAuthContext');

                return $guard;
            });
        };
    }
}
