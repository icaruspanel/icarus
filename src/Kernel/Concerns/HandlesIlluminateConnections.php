<?php
declare(strict_types=1);

namespace Icarus\Kernel\Concerns;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;

trait HandlesIlluminateConnections
{
    private ConnectionInterface $connection;

    private ?Grammar $grammar;

    private ?Processor $processor;

    protected function setConnection(
        ConnectionInterface $connection,
        ?Grammar            $grammar = null,
        ?Processor          $processor = null
    ): void
    {
        $this->connection = $connection;

        $this->setGrammar($grammar);
        $this->setProcessor($processor);
    }

    protected function setGrammar(?Grammar $grammar): void
    {
        $this->grammar = $grammar;
    }

    protected function setProcessor(?Processor $processor): void
    {
        $this->processor = $processor;
    }

    protected function query(): Builder
    {
        return new Builder(
            $this->connection,
            $this->grammar,
            $this->processor
        );
    }
}
