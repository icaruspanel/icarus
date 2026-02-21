<?php

namespace Icarus\Kernel\Modules\Contracts;

interface RouteCollector extends Collector
{
    /**
     * Register route mappers for the web routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     */
    public function web(string ...$mapperClasses): static;

    /**
     * Register route mappers for the API routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     */
    public function api(string ...$mapperClasses): static;

    /**
     * Register unscoped route mappers for the web routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     *
     * @throws \Icarus\Kernel\Modules\Exceptions\MissingCapability Thrown if the module does not have the cross-routes capability
     */
    public function unscopedWeb(string ...$mapperClasses): static;

    /**
     * Register unscoped route mappers for the API routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     *
     * @throws \Icarus\Kernel\Modules\Exceptions\MissingCapability Thrown if the module does not have the cross-routes capability
     */
    public function unscopedApi(string ...$mapperClasses): static;

    /**
     * Checks if the collector has any mappers registered.
     *
     * @return bool
     */
    public function hasMappers(): bool;
}
