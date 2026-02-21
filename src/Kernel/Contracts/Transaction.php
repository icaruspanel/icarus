<?php

namespace Icarus\Kernel\Contracts;

interface Transaction
{
    /**
     * Run the given callable in a transaction.
     *
     * @template TReturnType of mixed
     *
     * @param callable(): TReturnType $operation
     * @param int                     $tries
     *
     * @return mixed
     *
     * @phpstan-return TReturnType
     */
    public function run(callable $operation, int $tries = 1): mixed;
}
