<?php

declare(strict_types=1);

namespace App\Orders\Handler;

use App\Core\Transaction\TransactionManager;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Dto\Response\CreateOrderResponse;
use App\Orders\Service\CreateOrderService;

final class CreateOrderHandler
{
    public function __construct(private readonly TransactionManager $transactionManager, private readonly CreateOrderService $createOrderService)
    {
    }

    public function __invoke(CreateOrderRequest $request): CreateOrderResponse
    {
        return $this->transactionManager->transactional(
            fn (): CreateOrderResponse => $this->createOrderService->create($request),
        );
    }
}
