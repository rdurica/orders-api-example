<?php

declare(strict_types=1);

namespace App\Core\Transaction;

interface TransactionManager
{
    public function transactional(callable $callback): mixed;
}
