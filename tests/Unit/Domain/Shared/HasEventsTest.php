<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Stubs\EventRecorderStub;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('shared')]
class HasEventsTest extends TestCase
{
    #[Test]
    public function releaseEventsReturnsRecordedEvents(): void
    {
        $recorder = new EventRecorderStub();
        $event    = new stdClass();

        $recorder->record($event);

        $events = $recorder->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertSame($event, $events[0]);
    }

    #[Test]
    public function releaseEventsClearsTheQueue(): void
    {
        $recorder = new EventRecorderStub();

        $recorder->record(new stdClass());

        $first  = $recorder->releaseEvents();
        $second = $recorder->releaseEvents();

        $this->assertCount(1, $first);
        $this->assertCount(0, $second);
    }

    #[Test]
    public function multipleEventsAreRecordedInOrder(): void
    {
        $recorder = new EventRecorderStub();
        $eventA   = new stdClass();
        $eventB   = new stdClass();

        $recorder->record($eventA);
        $recorder->record($eventB);

        $events = $recorder->releaseEvents();

        $this->assertCount(2, $events);
        $this->assertSame($eventA, $events[0]);
        $this->assertSame($eventB, $events[1]);
    }

    #[Test]
    public function releaseEventsReturnsEmptyArrayWhenNoEventsRecorded(): void
    {
        $recorder = new EventRecorderStub();

        $events = $recorder->releaseEvents();

        $this->assertIsArray($events);
        $this->assertCount(0, $events);
    }
}
