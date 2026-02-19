<?php
declare(strict_types=1);

namespace Tests\Stubs;

use Icarus\Domain\Shared\CanBeCancelled;
use Icarus\Domain\Shared\Cancellable;

class CancellableStub implements Cancellable
{
    use CanBeCancelled;
}
