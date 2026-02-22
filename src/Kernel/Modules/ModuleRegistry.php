<?php
declare(strict_types=1);

namespace Icarus\Kernel\Modules;

use Icarus\Kernel\Modules\Attributes\NoContext;
use Icarus\Kernel\Modules\Attributes\Register;
use Icarus\Kernel\Modules\Contracts\Collector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class ModuleRegistry
{
    /**
     * @var array<string, \Icarus\Kernel\Modules\ModuleManifest>
     */
    private array $modules = [];

    /**
     * @param array $moduleData
     *
     * @return \Icarus\Kernel\Modules\ModuleManifest
     *
     * @throws \ReflectionException
     */
    public function resolve(array $moduleData): ModuleManifest
    {
        return $this->modules[$moduleData['ident']] = new ModuleManifest(
            $moduleData['ident'],
            $moduleData['name'],
            $moduleData['description'],
            $moduleData['definition'],
            array_map(static fn (string $capability) => Capability::from($capability), $moduleData['capabilities']),
            $moduleData['dependencies'],
            $moduleData['after'],
            $this->collectRegistrations($moduleData['definition']),
        );
    }

    /**
     * @param class-string $class
     *
     * @return array<\Icarus\Kernel\Modules\RegistrationEntry>
     *
     * @throws \ReflectionException
     */
    private function collectRegistrations(string $class): array
    {
        $reflector = new ReflectionClass($class);
        $entries   = [];

        foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // We don't want static methods or methods without parameters.
            if (! $method->isStatic() || $method->getNumberOfParameters() === 0) {
                continue;
            }

            $attribute = $method->getAttributes(Register::class)[0] ?? null;

            // We also don't want methods without the register attribute
            if ($attribute === null) {
                continue;
            }

            $collector = null;

            // Find the collector parameter
            foreach ($method->getParameters() as $parameter) {
                if (
                    $parameter->getType() instanceof ReflectionNamedType
                    && is_subclass_of($parameter->getType()->getName(), Collector::class)
                ) {
                    $collector = $parameter->getType()->getName();
                    break;
                }
            }

            // If there is no collector, we don't care about this method
            if ($collector === null) {
                continue;
            }

            /** @var \Icarus\Kernel\Modules\Attributes\Register $register */
            $register = $attribute->newInstance();

            $entries[] = new RegistrationEntry(
                $method->getName(),
                $collector,
                $register->operatingContext,
                ! empty($method->getAttributes(NoContext::class))
            );
        }

        return $entries;
    }
}
