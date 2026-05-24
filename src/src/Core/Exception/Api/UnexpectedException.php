<?php

declare(strict_types=1);

namespace App\Core\Exception\Api;

use App\Core\Enum\ApiErrorCode;

final class UnexpectedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(ApiErrorCode::UNEXPECTED, 'An unexpected error occurred.', 500);
    }
}
