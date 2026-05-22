<?php

declare(strict_types=1);

namespace App\Orders\Exception;

use App\Core\Exception\ApiException;
use App\Core\Exception\ExceptionType;

final class OrderNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Order not found.', 404);

        $this->setType(ExceptionType::ORDER_NOT_FOUND);
    }
}
