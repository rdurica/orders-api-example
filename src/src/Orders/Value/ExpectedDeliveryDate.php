<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class ExpectedDeliveryDate
{
    private function __construct(private DateTimeImmutable $value)
    {
    }

    public static function create(string $value): self
    {
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $value);

        if ($date === false)
        {
            throw new InvalidValueException('Expected delivery date must be a valid RFC3339 datetime.');
        }

        return new self($date);
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }
}
