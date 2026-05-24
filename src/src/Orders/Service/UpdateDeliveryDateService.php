<?php

declare(strict_types=1);

namespace App\Orders\Service;

use App\Core\Dto\Response\SimpleResponse;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;

/**
 * Skeleton service — business logic will be implemented in step 2.
 *
 * Planned behaviour:
 * - OrderNotFoundException when order does not exist
 * - InvalidDateException for invalid delivery date
 * - idempotent success when delivery date is unchanged
 */
final class UpdateDeliveryDateService
{
    public function update(UpdateDeliveryDateRequest $request): SimpleResponse
    {
        return new SimpleResponse('Order delivery date was updated successfully.');
    }
}
