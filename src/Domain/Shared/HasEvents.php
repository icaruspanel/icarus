<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

/**
 * @phpstan-require-implements \Icarus\Domain\Shared\RecordsEvents
 */
trait HasEvents
{
    /**
     * @var array<object>
     */
    private array $events = [];

    /**
     * Release all queued events.
     *
     * @return array<object>
     */
    public function releaseEvents(): array
    {
        $events       = $this->events;
        $this->events = [];

        return $events;
    }

    /**
     * Record an event.
     *
     * @param object $event
     *
     * @return void
     */
    protected function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }
}
