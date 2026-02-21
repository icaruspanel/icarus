<?php
declare(strict_types=1);

namespace Icarus\Kernel\Persistence\Scopes;

use Icarus\Domain\Shared\OperatingContext;
use Illuminate\Database\Query\Builder;

final readonly class ScopedByContext
{
    public ?string $table;

    public OperatingContext $context;

    public function __construct(
        OperatingContext $context,
        ?string          $table = null
    )
    {
        $this->context = $context;
        $this->table   = $table;
    }

    public function __invoke(Builder $query): Builder
    {
        return $query->where(
            $this->table ? "{$this->table}.context" : 'context',
            '=',
            $this->context->value
        );
    }
}
