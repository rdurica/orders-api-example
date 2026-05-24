<?php

declare(strict_types=1);

namespace App\Orders\Handler;

use App\Core\Dto\Response\SimpleResponse;
use App\Core\Transaction\TransactionManager;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use App\Orders\Service\UpdateDeliveryDateService;

final class UpdateDeliveryDateHandler
{
    public function __construct(private readonly TransactionManager $transactionManager, private readonly UpdateDeliveryDateService $updateDeliveryDateService)
    {
    }

    public function __invoke(UpdateDeliveryDateRequest $request): SimpleResponse
    {
        return $this->transactionManager->transactional(
            fn (): SimpleResponse => $this->updateDeliveryDateService->update($request),
        );
    }
}
