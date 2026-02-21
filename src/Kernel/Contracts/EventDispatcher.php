<?php

namespace Icarus\Kernel\Contracts;

use Icarus\Domain\Shared\RecordsEvents;

interface EventDispatcher
{
    /**
     * Dispatch a single event.
     *
     * @param object $event
     *
     * @return void
     */
    public function dispatch(object $event): void;

    /**
     * Dispatch a single event until halted by an event listener.
     *
     * @param object $event
     *
     * @return void
     */
    public function dispatchUntilHalted(object $event): void;

    /**
     * Dispatch events from an event recorder.
     *
     * @param \Icarus\Domain\Shared\RecordsEvents $recorder
     *
     * @return void
     */
    public function dispatchFrom(RecordsEvents $recorder): void;
}
