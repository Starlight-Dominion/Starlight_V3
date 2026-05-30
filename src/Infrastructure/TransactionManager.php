<?php

declare(strict_types=1);

namespace sdo\Infrastructure;

use Illuminate\Database\Capsule\Manager as Capsule;

class TransactionManager
{
    public function transaction(callable $callback)
    {
        return Capsule::transaction($callback);
    }
}
