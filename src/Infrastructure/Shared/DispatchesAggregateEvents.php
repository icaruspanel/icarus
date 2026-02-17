<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Illuminate\Contracts\Events\Dispatcher;

trait DispatchesAggregateEvents
{
    private Dispatcher $dispatcher;

    public function setDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    protected function dispatchEvent(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    protected function dispatchEvents(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatchEvent($event);
        }
    }
}
