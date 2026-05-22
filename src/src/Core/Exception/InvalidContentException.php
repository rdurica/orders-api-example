<?php

declare(strict_types=1);

namespace App\Core\Exception;

final class InvalidContentException extends ApiException
{
    /** @param list<array{field: string, message: string}> $errors */
    public function __construct(array $errors)
    {
        parent::__construct('Submitted data does not match the expected format.', 400);

        $this->setType(ExceptionType::INVALID_CONTENT);

        foreach ($errors as $error)
        {
            $this->addError($error['field'], $error['message']);
        }
    }
}
