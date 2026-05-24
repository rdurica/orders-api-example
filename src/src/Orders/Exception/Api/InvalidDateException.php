<?php

declare(strict_types=1);

namespace App\Orders\Exception\Api;

use App\Core\Enum\ApiErrorCode;
use App\Core\Exception\Api\ApiException;

final class InvalidDateException extends ApiException
{
    public function __construct(string $message = 'Invalid delivery date.')
    {
        parent::__construct(ApiErrorCode::INVALID_DATE, $message, 400);
    }
}
