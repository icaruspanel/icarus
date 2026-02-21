<?php
/** @noinspection PhpUnnecessaryStaticReferenceInspection */
declare(strict_types=1);

namespace Icarus\Kernel\Modules\Collectors\Routes;

use Icarus\Domain\Shared\OperatingContext;
use Icarus\Kernel\Modules\Attributes\Module;
use Icarus\Kernel\Modules\Capability;
use Icarus\Kernel\Modules\Contracts\RouteCollector;
use Icarus\Kernel\Modules\Exceptions\MissingCapability;

final class IlluminateRouteCollector implements RouteCollector
{
    public readonly Module $module;

    private readonly bool $hasCrossRoutesCapability;

    /**
     * @var \Icarus\Domain\Shared\OperatingContext|null
     */
    public readonly ?OperatingContext $operatingContext;

    /**
     * @var array<class-string<\Icarus\Kernel\Contracts\RouteMapper>>
     */
    private(set) array $webMappers = [];

    /**
     * @var array<class-string<\Icarus\Kernel\Contracts\RouteMapper>>
     */
    private(set) array $apiMappers = [];

    /**
     * @var array<class-string<\Icarus\Kernel\Contracts\RouteMapper>>
     */
    private(set) array $unscopedWebMappers = [];

    /**
     * @var array<class-string<\Icarus\Kernel\Contracts\RouteMapper>>
     */
    private(set) array $unscopedApiMappers = [];

    public function __construct(
        Module            $module,
        bool              $hasCrossRoutesCapability,
        ?OperatingContext $operatingContext = null
    )
    {
        $this->module                   = $module;
        $this->hasCrossRoutesCapability = $hasCrossRoutesCapability;
        $this->operatingContext         = $operatingContext;
    }

    /**
     * Register route mappers for the web routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     */
    public function web(string ...$mapperClasses): static
    {
        $this->webMappers = array_merge($this->webMappers, $mapperClasses);

        return $this;
    }

    /**
     * Register route mappers for the API routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     */
    public function api(string ...$mapperClasses): static
    {
        $this->apiMappers = array_merge($this->apiMappers, $mapperClasses);

        return $this;
    }

    /**
     * Register unscoped route mappers for the web routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     *
     * @throws \Icarus\Kernel\Modules\Exceptions\MissingCapability Thrown if the module does not have the cross-routes capability
     */
    public function unscopedWeb(string ...$mapperClasses): static
    {
        $this->checkCrossRoutesCapability();

        $this->unscopedWebMappers = array_merge($this->unscopedWebMappers, $mapperClasses);

        return $this;
    }

    /**
     * Register unscoped route mappers for the API routes.
     *
     * @param class-string<\Icarus\Kernel\Contracts\RouteMapper> ...$mapperClasses
     *
     * @return $this
     *
     * @throws \Icarus\Kernel\Modules\Exceptions\MissingCapability Thrown if the module does not have the cross-routes capability
     */
    public function unscopedApi(string ...$mapperClasses): static
    {
        $this->checkCrossRoutesCapability();

        $this->unscopedApiMappers = array_merge($this->unscopedApiMappers, $mapperClasses);

        return $this;
    }

    public function hasMappers(): bool
    {
        return ! empty($this->webMappers)
               || ! empty($this->apiMappers)
               || ! empty($this->unscopedWebMappers)
               || ! empty($this->unscopedApiMappers);
    }

    private function checkCrossRoutesCapability(): void
    {
        if (! $this->hasCrossRoutesCapability) {
            MissingCapability::make($this->module, Capability::CrossRoutes);
        }
    }
}
