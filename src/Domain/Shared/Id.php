<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

use Stringable;
use Symfony\Component\Uid\Ulid;

/**
 *
 */
abstract readonly class Id implements Stringable
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

    /**
     * Check if the given ID is the same as this ID.
     *
     * @param \Icarus\Domain\Shared\Id $id
     *
     * @return bool
     */
    public function is(self $id): bool
    {
        return static::class === $id::class
               && $this->id === $id->id;
    }

    /**
     * Magic method {@see https://www.php.net/manual/en/language.oop5.magic.php#object.tostring}
     * allows a class to decide how it will react when it is treated like a string.
     *
     * @return string Returns string representation of the object that
     * implements this interface (and/or "__toString" magic method).
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
