<?php
declare(strict_types=1);

namespace Tests\Stubs;

use Icarus\Domain\Shared\HasEvents;
use Icarus\Domain\Shared\RecordsEvents;

class EventRecorderStub implements RecordsEvents
{
    use HasEvents;

    public function record(object $event): void
    {
        $this->recordEvent($event);
    }
}
