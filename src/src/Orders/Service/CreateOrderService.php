<?php

declare(strict_types=1);

namespace App\Orders\Service;

use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Dto\Response\CreateOrderResponse;

/**
 * Skeleton service — business logic will be implemented in step 2.
 *
 * Planned behaviour:
 * - create new order when partner_id + order_id does not exist
 * - idempotent success when existing order and all items match
 * - OrderAlreadyExistsException when order exists with different items
 */
final class CreateOrderService
{
    public function create(CreateOrderRequest $request): CreateOrderResponse
    {
        return new CreateOrderResponse();
    }
}
