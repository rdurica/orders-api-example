<?php

declare(strict_types=1);

namespace App\Orders\Service;

use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use App\Orders\Dto\Response\UpdateDeliveryDateResponse;

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
    public function update(UpdateDeliveryDateRequest $request): UpdateDeliveryDateResponse
    {
        $response = new UpdateDeliveryDateResponse();
        $response->success = true;

        return $response;
    }
}
