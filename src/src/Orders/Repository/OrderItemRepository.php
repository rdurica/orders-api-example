<?php

declare(strict_types=1);

namespace App\Orders\Repository;

use App\Orders\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OrderItem> */
final class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function save(OrderItem $orderItem): void
    {
        $this->getEntityManager()->persist($orderItem);
    }
}
