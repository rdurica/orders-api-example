<?php

declare(strict_types=1);

namespace App\Core\Exception;

final class UnexpectedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('An unexpected error occurred.', 500);

        $this->setType(ExceptionType::UNEXPECTED);
    }
}
