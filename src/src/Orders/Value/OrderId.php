<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;

final readonly class OrderId
{
    private function __construct(private string $value)
    {
    }

    public static function create(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '')
        {
            throw new InvalidValueException('Order ID must not be blank.');
        }

        if (strlen($trimmed) > 64)
        {
            throw new InvalidValueException('Order ID must not exceed 64 characters.');
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }
}
