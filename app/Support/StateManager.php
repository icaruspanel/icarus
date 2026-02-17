<?php
declare(strict_types=1);

namespace App\Support;

final class StateManager
{
    /**
     * @var array<class-string, object>
     */
    private array $state = [];

    /**
     * Set the state of the given type.
     *
     * @template S of object
     *
     * @param object                       $state
     * @param class-string|null            $class
     *
     * @phpstan-param S                    $state
     * @phpstan-param class-string<S>|null $class
     *
     * @return $this
     */
    public function setState(object $state, ?string $class = null): self
    {
        $this->state[$class ?? $state::class] = $state;

        return $this;
    }

    /**
     * Get the state of the given type.
     *
     * @template S of object
     *
     * @param class-string            $class
     *
     * @phpstan-param class-string<S> $class
     *
     * @return object|null
     *
     * @phpstan-return S|null
     */
    public function getState(string $class): ?object
    {
        /** @var S|null $state */
        $state = $this->state[$class] ?? null;

        return $state;
    }
}
