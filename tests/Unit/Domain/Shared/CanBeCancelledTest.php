<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Stubs\CancellableStub;
use Tests\TestCase;

#[Group('unit'), Group('core'), Group('domain'), Group('shared')]
class CanBeCancelledTest extends TestCase
{
    #[Test]
    public function defaultsToNotCancelled(): void
    {
        $stub = new CancellableStub();

        $this->assertFalse($stub->isCancelled());
        $this->assertTrue($stub->isAllowed());
        $this->assertNull($stub->getCancelReason());
    }

    #[Test]
    public function cancelSetsCancelledState(): void
    {
        $stub = new CancellableStub();

        $stub->cancel();

        $this->assertTrue($stub->isCancelled());
        $this->assertFalse($stub->isAllowed());
    }

    #[Test]
    public function cancelStoresReason(): void
    {
        $stub = new CancellableStub();

        $stub->cancel('IP banned');

        $this->assertTrue($stub->isCancelled());
        $this->assertSame('IP banned', $stub->getCancelReason());
    }

    #[Test]
    public function cancelDefaultsReasonToNull(): void
    {
        $stub = new CancellableStub();

        $stub->cancel();

        $this->assertTrue($stub->isCancelled());
        $this->assertNull($stub->getCancelReason());
    }

    #[Test]
    public function allowResetsCancelledState(): void
    {
        $stub = new CancellableStub();

        $stub->cancel('Some reason');

        $this->assertTrue($stub->isCancelled());
        $this->assertFalse($stub->isAllowed());
        $this->assertSame('Some reason', $stub->getCancelReason());

        $stub->allow();

        $this->assertFalse($stub->isCancelled());
        $this->assertTrue($stub->isAllowed());
        $this->assertNull($stub->getCancelReason());
    }

    #[Test]
    public function cancelCanBeCalledMultipleTimes(): void
    {
        $stub = new CancellableStub();

        $stub->cancel('First reason');

        $this->assertSame('First reason', $stub->getCancelReason());

        $stub->cancel('Second reason');

        $this->assertTrue($stub->isCancelled());
        $this->assertSame('Second reason', $stub->getCancelReason());
    }
}
