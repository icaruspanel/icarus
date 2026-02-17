<?php
declare(strict_types=1);

namespace App\Boot;

use App\Http\Routes\DefaultRoutes;
use Closure;
use Illuminate\Routing\Router;

final class ConfigureRouting
{
    public static function make(): Closure
    {
        return new self()(...);
    }

    /**
     * @var array<string, list<class-string<\App\Support\RouteMapper>>>
     */
    private static array $mappers = [
        'web' => [
            DefaultRoutes::class,
        ],
    ];

    public function __invoke(Router $router): void
    {
        foreach (self::$mappers as $group => $mappers) {
            $method = 'map' . ucfirst($group);

            if (method_exists($this, $method)) {
                $this->$method($router, $mappers);
            }
        }
    }

    /**
     * @param list<class-string<\App\Support\RouteMapper>> $mappers
     */
    private function mapWeb(Router $router, array $mappers): void
    {
        foreach ($mappers as $mapper) {
            (new $mapper)->map($router);
        }
    }
}
