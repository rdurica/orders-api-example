<?php

declare(strict_types=1);

namespace App\Core\Enum;

enum ApiErrorCode: string
{
    case INVALID_CONTENT = 'invalid_content';

    case INVALID_DATA = 'invalid_data';

    case UNEXPECTED = 'unexpected';

    case ORDER_ALREADY_EXISTS = 'order_already_exists';

    case ORDER_NOT_FOUND = 'order_not_found';

    case INVALID_DATE = 'invalid_date';
}
