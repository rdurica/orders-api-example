<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;

final readonly class ProductTitle
{
    private function __construct(private string $value)
    {
    }

    public static function create(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '')
        {
            throw new InvalidValueException('Product title must not be blank.');
        }

        if (strlen($trimmed) > 255)
        {
            throw new InvalidValueException('Product title must not exceed 255 characters.');
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }
}
