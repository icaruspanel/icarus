<?php
declare(strict_types=1);

namespace Icarus\Kernel\Permission;

use Icarus\Domain\Permission\RoleRepository;
use Illuminate\Support\ServiceProvider;

class PermissionDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepository();
    }

    private function registerRepository(): void
    {
        $this->app->bind(RoleRepository::class, IlluminateRoleRepository::class);
    }
}
