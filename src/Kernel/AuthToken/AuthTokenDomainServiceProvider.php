<?php
declare(strict_types=1);

namespace Icarus\Kernel\AuthToken;

use Icarus\Domain\AuthToken\AuthTokenRepository;
use Illuminate\Support\ServiceProvider;

class AuthTokenDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepository();
    }

    private function registerRepository(): void
    {
        $this->app->bind(AuthTokenRepository::class, IlluminateAuthTokenRepository::class);
    }
}
