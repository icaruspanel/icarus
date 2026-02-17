<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\Shared;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

trait HandlesIlluminateConnections
{
    private ConnectionInterface $connection;

    protected function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    protected function query(): Builder
    {
        return new Builder(
            $this->connection,
            $this->connection->getQueryGrammar(),
            $this->connection->getPostProcessor()
        );
    }
}
