<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\Shared\RecordsEvents;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class IlluminateEventDispatcher implements EventDispatcher
{
    private Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch a single event.
     *
     * @param object $event
     *
     * @return void
     */
    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    /**
     * Dispatch a single event until halted by an event listener.
     *
     * @param object $event
     *
     * @return void
     */
    public function dispatchUntilHalted(object $event): void
    {
        $this->dispatcher->dispatch($event, halt: true);
    }

    /**
     * Dispatch events from an event recorder.
     *
     * @param \Icarus\Domain\Shared\RecordsEvents $recorder
     *
     * @return void
     */
    public function dispatchFrom(RecordsEvents $recorder): void
    {
        $events = $recorder->releaseEvents();

        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
