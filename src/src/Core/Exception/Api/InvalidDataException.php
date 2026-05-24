<?php

declare(strict_types=1);

namespace App\Core\Exception\Api;

use App\Core\Enum\ApiErrorCode;

final class InvalidDataException extends ApiException
{
    /** @param list<array{field: string, message: string}> $errors */
    public function __construct(array $errors = [], string $message = 'Submitted data does not match the expected format.')
    {
        parent::__construct(ApiErrorCode::INVALID_DATA, $message, 400);

        foreach ($errors as $error)
        {
            $this->addError($error['field'], $error['message']);
        }
    }
}
