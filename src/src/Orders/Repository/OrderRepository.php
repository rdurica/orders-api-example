<?php

declare(strict_types=1);

namespace App\Orders\Repository;

use App\Orders\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Order> */
final class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByPartnerAndOrderId(string $partnerId, string $orderId): ?Order
    {
        return $this->findOneBy([
            'partnerId' => $partnerId,
            'orderId'   => $orderId,
        ]);
    }

    public function save(Order $order): void
    {
        $this->getEntityManager()->persist($order);
    }
}
