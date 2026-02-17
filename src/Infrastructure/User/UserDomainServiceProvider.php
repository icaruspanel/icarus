<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\User;

use Icarus\Domain\User\UserRepository;
use Illuminate\Support\ServiceProvider;

class UserDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepository();
    }

    private function registerRepository(): void
    {
        $this->app->bind(UserRepository::class, IlluminateUserRepository::class);
    }
}
