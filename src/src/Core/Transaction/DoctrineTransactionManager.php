<?php

declare(strict_types=1);

namespace App\Core\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionManager implements TransactionManager
{
    public function __construct(private readonly Connection $connection, private readonly EntityManagerInterface $em)
    {
    }

    public function transactional(callable $callback): mixed
    {
        return $this->connection->transactional(function () use ($callback)
        {
            $result = $callback();
            $this->em->flush();

            return $result;
        });
    }
}
