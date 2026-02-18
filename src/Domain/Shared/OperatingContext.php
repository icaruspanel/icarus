<?php
declare(strict_types=1);

namespace Icarus\Domain\Shared;

enum OperatingContext: string
{
    case Account = 'account';

    case Platform = 'platform';
}
