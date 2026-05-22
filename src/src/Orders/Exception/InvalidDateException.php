<?php

declare(strict_types=1);

namespace App\Orders\Exception;

use App\Core\Exception\ApiException;
use App\Core\Exception\ExceptionType;

final class InvalidDateException extends ApiException
{
    public function __construct(string $message = 'Invalid delivery date.')
    {
        parent::__construct($message, 400);

        $this->setType(ExceptionType::INVALID_DATE);
    }
}
