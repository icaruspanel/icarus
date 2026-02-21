<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,

    // The main kernel provider should be registered and booted before
    // all others.
    Icarus\Kernel\KernelServiceProvider::class,

    // Icarus Domain Providers are registered after the kernel but before
    // the modules.
    Icarus\Kernel\User\UserDomainServiceProvider::class,
    Icarus\Kernel\AuthToken\AuthTokenDomainServiceProvider::class,

    // Register and boot module-specific functionality. Is required before
    // almost everything else too.
    Icarus\Kernel\Modules\ModuleServiceProvider::class,
];
