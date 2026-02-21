<?php
declare(strict_types=1);

namespace Tests\Unit\Kernel;

use Icarus\Kernel\IlluminateEventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Stubs\EventRecorderStub;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('infrastructure'), Group('shared')]
class IlluminateEventDispatcherTest extends TestCase
{
    private Dispatcher&MockInterface $laravelDispatcher;

    private IlluminateEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laravelDispatcher = Mockery::mock(Dispatcher::class);
        $this->dispatcher        = new IlluminateEventDispatcher($this->laravelDispatcher);
    }

    #[Test]
    public function dispatchDelegatesToLaravelDispatcher(): void
    {
        $event = new stdClass();

        $this->laravelDispatcher->shouldReceive('dispatch')
                                ->with($event)
                                ->once();

        $this->dispatcher->dispatch($event);
    }

    #[Test]
    public function dispatchUntilHaltedDelegatesToLaravelDispatcherWithHalt(): void
    {
        $event = new stdClass();

        $this->laravelDispatcher->shouldReceive('dispatch')
                                ->withArgs(function ($dispatched, ...$extra) use ($event) {
                                    return $dispatched === $event
                                           && in_array(true, $extra, true);
                                })
                                ->once();

        $this->dispatcher->dispatchUntilHalted($event);
    }

    #[Test]
    public function dispatchFromReleasesAndDispatchesAllEvents(): void
    {
        $recorder = new EventRecorderStub();
        $eventA   = new stdClass();
        $eventB   = new stdClass();

        $recorder->record($eventA);
        $recorder->record($eventB);

        $this->laravelDispatcher->shouldReceive('dispatch')
                                ->with($eventA)
                                ->once();

        $this->laravelDispatcher->shouldReceive('dispatch')
                                ->with($eventB)
                                ->once();

        $this->dispatcher->dispatchFrom($recorder);
    }

    #[Test]
    public function dispatchFromDoesNothingWhenNoEvents(): void
    {
        $recorder = new EventRecorderStub();

        $this->laravelDispatcher->shouldNotReceive('dispatch');

        $this->dispatcher->dispatchFrom($recorder);
    }

    #[Test]
    public function dispatchFromClearsEventsFromRecorder(): void
    {
        $recorder = new EventRecorderStub();

        $recorder->record(new stdClass());

        $this->laravelDispatcher->shouldReceive('dispatch')->once();

        $this->dispatcher->dispatchFrom($recorder);

        $this->assertCount(0, $recorder->releaseEvents());
    }
}
