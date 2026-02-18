<?php
declare(strict_types=1);

namespace App;

use Icarus\Domain\Shared\AuthContext;
use Icarus\Domain\Shared\OperatingContext;

final class Icarus
{
    private(set) ?OperatingContext $context = null;

    private(set) ?AuthContext $authContext = null;

    public function getAuthContext(): ?AuthContext
    {
        return $this->authContext;
    }

    public function setAuthContext(?AuthContext $authContext): self
    {
        $this->authContext = $authContext;

        return $this;
    }

    public function getContext(): ?OperatingContext
    {
        return $this->context;
    }

    public function setContext(?OperatingContext $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return bool
     *
     * @phpstan-assert-if-true OperatingContext $this->context
     * @phpstan-assert-if-false null $this->context
     */
    public function hasContext(): bool
    {
        return $this->getContext() !== null;
    }
}
