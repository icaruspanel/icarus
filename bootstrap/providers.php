<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,

    // Icarus Providers
    Icarus\Infrastructure\Shared\SharedServiceProvider::class,

    // Icarus Domain Providers
    Icarus\Infrastructure\User\UserDomainServiceProvider::class,
    Icarus\Infrastructure\AuthToken\AuthTokenDomainServiceProvider::class,
];
