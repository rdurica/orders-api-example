<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\Quantity;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    /** @return Iterator<string, array{input: int, expected: int}> */
    public static function validQuantityProvider(): Iterator
    {
        yield 'positive integer single digit' => [
            'input'    => 1,
            'expected' => 1,
        ];

        yield 'positive integer two digits' => [
            'input'    => 99,
            'expected' => 99,
        ];

        yield 'positive integer without decimals' => [
            'input'    => 10,
            'expected' => 10,
        ];

        yield 'positive integer thousands without decimals' => [
            'input'    => 1000,
            'expected' => 1000,
        ];

        yield 'small positive' => [
            'input'    => 2,
            'expected' => 2,
        ];

        yield 'medium positive' => [
            'input'    => 100,
            'expected' => 100,
        ];

        yield 'maximum valid' => [
            'input'    => 100_000,
            'expected' => 100_000,
        ];
    }

    /**
     * Ověřuje, že Quantity::create() přijímá kladná celá čísla v rozsahu 1 až 100_000.
     *
     * Vstup: páry celých čísel z validQuantityProvider (např. 1, 99, 100_000).
     * Důvod: množství položky objednávky musí být kladné celé číslo v definovaném rozsahu.
     */
    #[DataProvider('validQuantityProvider')]
    public function testHappyFlowCreateAcceptsPositiveIntegers(int $input, int $expected): void
    {
        $quantity = Quantity::create($input);

        self::assertSame($expected, $quantity->value());
    }

    /** @return Iterator<string, array{input: int, expectedMessage: string}> */
    public static function invalidQuantityProvider(): Iterator
    {
        yield 'zero integer without decimals' => [
            'input'           => 0,
            'expectedMessage' => 'Quantity must be a positive integer.',
        ];

        yield 'negative integer single digit' => [
            'input'           => -1,
            'expectedMessage' => 'Quantity must be a positive integer.',
        ];

        yield 'negative integer without decimals' => [
            'input'           => -10,
            'expectedMessage' => 'Quantity must be a positive integer.',
        ];

        yield 'large negative' => [
            'input'           => -100,
            'expectedMessage' => 'Quantity must be a positive integer.',
        ];

        yield 'exceeds maximum by one' => [
            'input'           => 100_001,
            'expectedMessage' => 'Quantity must not exceed 100000.',
        ];

        yield 'far above maximum' => [
            'input'           => 999_999,
            'expectedMessage' => 'Quantity must not exceed 100000.',
        ];
    }

    /**
     * Ověřuje, že Quantity::create() odmítá nulu, záporné hodnoty a hodnoty nad 100_000.
     *
     * Vstup: celá čísla z invalidQuantityProvider (např. 0, -1, 100_001).
     * Důvod: množství musí být kladné celé číslo v rozsahu 1 až 100_000.
     */
    #[DataProvider('invalidQuantityProvider')]
    public function testCreateRejectsInvalidQuantities(int $input, string $expectedMessage): void
    {
        try
        {
            Quantity::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%d".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
