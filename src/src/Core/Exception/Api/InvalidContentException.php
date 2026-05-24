<?php

declare(strict_types=1);

namespace App\Core\Exception\Api;

use App\Core\Enum\ApiErrorCode;

final class InvalidContentException extends ApiException
{
    /** @param list<array{field: string, message: string}> $errors */
    public function __construct(array $errors)
    {
        parent::__construct(ApiErrorCode::INVALID_CONTENT, 'Submitted data does not match the expected format.', 400);

        foreach ($errors as $error)
        {
            $this->addError($error['field'], $error['message']);
        }
    }
}
