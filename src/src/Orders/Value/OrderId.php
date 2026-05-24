<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;

final readonly class OrderId
{
    private function __construct(private string $value)
    {
        if (trim($value) === '')
        {
            throw new InvalidValueException('Order ID must not be blank.');
        }

        if (strlen($value) > 64)
        {
            throw new InvalidValueException('Order ID must not exceed 64 characters.');
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
