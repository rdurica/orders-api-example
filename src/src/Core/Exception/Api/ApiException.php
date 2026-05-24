<?php

declare(strict_types=1);

namespace App\Core\Exception\Api;

use App\Core\Enum\ApiErrorCode;
use Exception;
use Throwable;

abstract class ApiException extends Exception
{
    /** @var list<array{field: string, message: string}> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly ApiErrorCode $errorCode, string $message = '', int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function errorCode(): ApiErrorCode
    {
        return $this->errorCode;
    }

    /** @return list<array{field: string, message: string}> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[] = [
            'field'   => $field,
            'message' => $message,
        ];
    }

    protected function setData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
