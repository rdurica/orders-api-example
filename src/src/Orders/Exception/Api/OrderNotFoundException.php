<?php

declare(strict_types=1);

namespace App\Orders\Exception\Api;

use App\Core\Enum\ApiErrorCode;
use App\Core\Exception\Api\ApiException;

final class OrderNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct(ApiErrorCode::ORDER_NOT_FOUND, 'Order not found.', 404);
    }
}
