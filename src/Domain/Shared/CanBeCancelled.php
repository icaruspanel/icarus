<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

/**
 * @phpstan-require-implements \Icarus\Domain\Shared\Cancellable
 */
trait CanBeCancelled
{
    private bool $cancelled = false;

    private ?string $reason = null;

    /**
     * Cancel the event.
     *
     * @param string|null $reason
     *
     * @return void
     */
    public function cancel(?string $reason = null): void
    {
        $this->cancelled = true;
        $this->reason    = $reason;
    }

    /**
     * Allow the event to continue.
     *
     * @return void
     */
    public function allow(): void
    {
        $this->cancelled = false;
        $this->reason    = null;
    }

    /**
     * Check if the event has been cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Check if the event is allowed to continue.
     * @return bool
     */
    public function isAllowed(): bool
    {
        return ! $this->cancelled;
    }

    /**
     * Get the reason why the event has been cancelled.
     *
     * @return string|null
     */
    public function getCancelReason(): ?string
    {
        return $this->reason;
    }
}
