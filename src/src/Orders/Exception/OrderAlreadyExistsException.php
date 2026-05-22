<?php

declare(strict_types=1);

namespace App\Orders\Exception;

use App\Core\Exception\ApiException;
use App\Core\Exception\ExceptionType;

final class OrderAlreadyExistsException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Order with this number already exists.', 409);

        $this->setType(ExceptionType::ORDER_ALREADY_EXISTS);
    }
}
