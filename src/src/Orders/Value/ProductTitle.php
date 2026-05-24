<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;

final readonly class ProductTitle
{
    private function __construct(private string $value)
    {
        if (trim($value) === '')
        {
            throw new InvalidValueException('Product title must not be blank.');
        }

        if (strlen($value) > 255)
        {
            throw new InvalidValueException('Product title must not exceed 255 characters.');
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
