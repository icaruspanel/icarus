<?php

namespace Icarus\Domain\Shared;

/**
 * Records Events
 *
 * Marks an aggregate root as being able to record events, and then release
 * together.
 */
interface RecordsEvents
{
    /**
     * Release all queued events.
     *
     * @return array<object>
     */
    public function releaseEvents(): array;
}
