<?php

namespace Icarus\Domain\Shared;

interface Cancellable
{
    /**
     * Cancel the event.
     *
     * @param string|null $reason
     *
     * @return void
     */
    public function cancel(?string $reason = null): void;

    /**
     * Allow the event to continue.
     *
     * @return void
     */
    public function allow(): void;

    /**
     * Check if the event has been cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool;

    /**
     * Check if the event is allowed to continue.
     * @return bool
     */
    public function isAllowed(): bool;

    /**
     * Get the reason why the event has been cancelled.
     *
     * @return string|null
     */
    public function getCancelReason(): ?string;
}
