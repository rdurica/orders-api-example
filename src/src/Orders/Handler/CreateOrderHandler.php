<?php

declare(strict_types=1);

namespace App\Orders\Handler;

use App\Core\Dto\Response\SimpleResponse;
use App\Core\Transaction\TransactionManager;
use App\Orders\Dto\ProductDto;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Exception\Domain\OrderAlreadyExistsException;
use App\Orders\Factory\OrderEntityFactory;
use App\Orders\Repository\OrderRepository;
use App\Orders\Value\ExpectedDeliveryDate;
use App\Orders\Value\OrderId;
use App\Orders\Value\OrderItem;
use App\Orders\Value\PartnerId;

final class CreateOrderHandler
{
    public function __construct(
        private readonly TransactionManager $transactionManager,
        private readonly OrderRepository $orderRepository,
        private readonly OrderEntityFactory $orderEntityFactory,
    )
    {
    }

    /**
     * @throws InvalidValueException
     * @throws OrderAlreadyExistsException
     */
    public function __invoke(CreateOrderRequest $request): SimpleResponse
    {
        $partnerId    = PartnerId::create($request->partnerId);
        $orderId      = OrderId::create($request->orderId);
        $deliveryDate = ExpectedDeliveryDate::create($request->expectedDeliveryDate);
        $orderItems   = CreateOrderHandler::createOrderItems($request->products);

        // Teoreticky muze nastat RaceCondition jelikoz delame entity mimo transakci.
        // Nechci mit ale dlouhe transakce a toto si myslim je vhodny kompromis co se tyce vykonu/komplexity.
        $isSameOrderCreated = $this->isSameOrderCreated($partnerId, $orderId, $orderItems);
        if ($isSameOrderCreated === true)
        {
            return new SimpleResponse('Order was created successfully.');
        }

        $orderEntity = $this->orderEntityFactory->create($partnerId, $orderId, $deliveryDate, $orderItems);
        $this->transactionManager->transactional(function () use ($orderEntity): void
        {
            $this->orderRepository->save($orderEntity);
        });

        return new SimpleResponse('Order was created successfully.');
    }

    /**
     * Zkontroluje, zda objednavka partnera jiz je vytvorena nebo nikoli.
     *
     * @param list<OrderItem> $orderItems
     *
     * @throws OrderAlreadyExistsException
     */
    private function isSameOrderCreated(PartnerId $partnerId, OrderId $orderId, array $orderItems): bool
    {
        $existingOrderEntity = $this->orderRepository->findByPartnerAndOrderId(
            $partnerId->value(),
            $orderId->value(),
        );

        if ($existingOrderEntity === null)
        {
            return false;
        }

        if (!$existingOrderEntity->hasSameItemsAs($orderItems))
        {
            throw new OrderAlreadyExistsException();
        }

        return true;
    }

    /**
     * @param list<ProductDto> $products
     *
     * @return list<OrderItem>
     *
     * @throws InvalidValueException
     */
    private static function createOrderItems(array $products): array
    {
        if (count($products) === 0)
        {
            throw new InvalidValueException('Order must contain at least one product.');
        }

        $orderItems = [];
        foreach ($products as $product)
        {
            $orderItems[] = OrderItem::create($product);
        }

        return $orderItems;
    }
}
