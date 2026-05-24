<?php

declare(strict_types=1);

namespace App\Orders\Handler;

use App\Core\Dto\Response\SimpleResponse;
use App\Core\Transaction\TransactionManager;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Exception\Domain\OrderNotFoundException;
use App\Orders\Repository\OrderRepository;
use App\Orders\Value\ExpectedDeliveryDate;
use App\Orders\Value\OrderId;
use App\Orders\Value\PartnerId;
use DateMalformedStringException;

final class UpdateDeliveryDateHandler
{
    public function __construct(private readonly TransactionManager $transactionManager, private readonly OrderRepository $orderRepository)
    {
    }

    /**
     * @throws InvalidValueException
     * @throws OrderNotFoundException
     * @throws DateMalformedStringException
     */
    public function __invoke(UpdateDeliveryDateRequest $request): SimpleResponse
    {
        $partnerId    = PartnerId::create($request->partnerId);
        $orderId      = OrderId::create($request->orderId);
        $deliveryDate = ExpectedDeliveryDate::create($request->expectedDeliveryDate);

        $orderEntity = $this->orderRepository->findByPartnerAndOrderId(
            $partnerId->value(),
            $orderId->value(),
        );

        if ($orderEntity === null)
        {
            throw new OrderNotFoundException();
        }

        if ($orderEntity->hasSameExpectedDeliveryDateAs($deliveryDate))
        {
            return new SimpleResponse('Order delivery date was updated successfully.');
        }

        // Ulozime - v entite mimo transakci transakce jen ulozi novou hodnotu.
        $orderEntity->setExpectedDeliveryDate($deliveryDate->value());
        $this->transactionManager->transactional(function () use ($orderEntity): void
        {
            $this->orderRepository->save($orderEntity);
        });

        return new SimpleResponse('Order delivery date was updated successfully.');
    }
}
