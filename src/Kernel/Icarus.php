<?php
declare(strict_types=1);

namespace Icarus\Kernel;

use Icarus\Domain\Shared\AuthContext;
use Icarus\Domain\Shared\OperatingContext;

final class Icarus
{
    private(set) ?OperatingContext $operatingContext = null;

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

    public function hasAuthContext(): bool
    {
        return $this->authContext !== null;
    }

    public function getOperatingContext(): ?OperatingContext
    {
        return $this->operatingContext;
    }

    public function setOperatingContext(?OperatingContext $operatingContext): self
    {
        $this->operatingContext = $operatingContext;

        return $this;
    }

    /**
     * @return bool
     *
     * @phpstan-assert-if-true OperatingContext $this->operatingContext
     * @phpstan-assert-if-false null $this->operatingContext
     */
    public function hasOperatingContext(): bool
    {
        return $this->getOperatingContext() !== null;
    }
}
