<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;

final readonly class Price
{
    private const string DECIMAL_PATTERN = '/^\d+(\.\d{1,2})?$/';

    private function __construct(private string $value)
    {
    }

    public static function create(string $value): self
    {
        if (!preg_match(self::DECIMAL_PATTERN, $value))
        {
            throw new InvalidValueException('Price must be a positive decimal with up to 2 decimal places.');
        }

        /** @var numeric-string $decimal */
        $decimal = $value;

        if (bccomp($decimal, '0', 2) <= 0)
        {
            throw new InvalidValueException('Price must be a positive number.');
        }

        return new self(bcadd($decimal, '0', 2));
    }

    public function value(): string
    {
        return $this->value;
    }
}
