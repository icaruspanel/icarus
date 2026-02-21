<?php
declare(strict_types=1);

namespace Icarus\Kernel\Persistence;

use Icarus\Kernel\Contracts\Transaction;
use Illuminate\Database\ConnectionInterface;

final readonly class IlluminateTransaction implements Transaction
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

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
     *
     * @throws \Throwable
     */
    public function run(callable $operation, int $tries = 1): mixed
    {
        return $this->connection->transaction(
            $operation(...),
            $tries
        );
    }
}
