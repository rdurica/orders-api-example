<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;

final readonly class Quantity
{
    private function __construct(private int $value)
    {
    }

    public static function create(int $value): self
    {
        if ($value <= 0)
        {
            throw new InvalidValueException('Quantity must be a positive integer.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
