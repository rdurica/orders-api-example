<?php

declare(strict_types=1);

namespace App\Orders\Exception\Api;

use App\Core\Enum\ApiErrorCode;
use App\Core\Exception\Api\ApiException;

final class OrderAlreadyExistsException extends ApiException
{
    public function __construct()
    {
        parent::__construct(ApiErrorCode::ORDER_ALREADY_EXISTS, 'Order with this number already exists.', 409);
    }
}
