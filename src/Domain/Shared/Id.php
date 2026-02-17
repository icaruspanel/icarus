<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

use Symfony\Component\Uid\Ulid;

/**
 *
 */
abstract readonly class Id
{
    /**
     * @return static
     */
    public static function generate(): static
    {
        return new static(Ulid::generate());
    }

    /**
     * @var string
     */
    public string $id;

    /**
     * @param string $id
     */
    final public function __construct(string $id)
    {
        assert(Ulid::isValid($id), 'Invalid ULID format');

        $this->id = $id;
    }
}
