<?php

declare(strict_types=1);

namespace App\Orders\Factory;

use App\Orders\Entity\Order;
use App\Orders\Entity\OrderItem as OrderItemEntity;
use App\Orders\Value\ExpectedDeliveryDate;
use App\Orders\Value\OrderId;
use App\Orders\Value\OrderItem;
use App\Orders\Value\PartnerId;

final class OrderEntityFactory
{
    /**
     * @param list<OrderItem> $orderItems
     */
    public function create(PartnerId $partnerId, OrderId $orderId, ExpectedDeliveryDate $deliveryDate, array $orderItems): Order
    {
        $orderEntity = new Order(
            $partnerId->value(),
            $orderId->value(),
            $deliveryDate->value(),
        );

        foreach ($orderItems as $orderItem)
        {
            $orderEntity->addItem($this->createOrderItemEntity($orderItem));
        }

        return $orderEntity;
    }

    private function createOrderItemEntity(OrderItem $orderItem): OrderItemEntity
    {
        return new OrderItemEntity(
            $orderItem->productId()->value(),
            $orderItem->title()->value(),
            $orderItem->price()->value(),
            $orderItem->quantity()->value(),
        );
    }
}
