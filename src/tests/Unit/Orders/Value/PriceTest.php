<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\Price;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    /** @return Iterator<string, array{input: string, expected: string}> */
    public static function validPriceProvider(): Iterator
    {
        yield 'positive integer single digit' => [
            'input'    => '1',
            'expected' => '1.00',
        ];

        yield 'positive integer two digits' => [
            'input'    => '99',
            'expected' => '99.00',
        ];

        yield 'positive integer without decimals' => [
            'input'    => '10',
            'expected' => '10.00',
        ];

        yield 'positive integer thousands without decimals' => [
            'input'    => '1000',
            'expected' => '1000.00',
        ];

        yield 'positive integer with leading zeros normalized' => [
            'input'    => '00010',
            'expected' => '10.00',
        ];

        yield 'one decimal zero normalized' => [
            'input'    => '10.0',
            'expected' => '10.00',
        ];

        yield 'one decimal place padded' => [
            'input'    => '0.1',
            'expected' => '0.10',
        ];

        yield 'one decimal' => [
            'input'    => '10.5',
            'expected' => '10.50',
        ];

        yield 'two decimals' => [
            'input'    => '10.50',
            'expected' => '10.50',
        ];

        yield 'normalization' => [
            'input'    => '199.9',
            'expected' => '199.90',
        ];

        yield 'minimum valid' => [
            'input'    => '0.01',
            'expected' => '0.01',
        ];

        yield 'leading zero normalized' => [
            'input'    => '01.5',
            'expected' => '1.50',
        ];

        yield 'large value' => [
            'input'    => '999999.99',
            'expected' => '999999.99',
        ];

        yield 'trailing zero normalization' => [
            'input'    => '10.00',
            'expected' => '10.00',
        ];
    }

    /**
     * Ověřuje, že Price::create() přijímá platné desetinné řetězce a normalizuje je.
     * Vstup: páry řetězců z validPriceProvider (např. "10" => "10.00", "0.01" => "0.01").
     * Důvod: cena musí být uložena jako normalizované desetinné číslo s přesně 2 desetinnými místy.
     */
    #[DataProvider('validPriceProvider')]
    public function testHappyFlowCreateAcceptsValidPrices(string $input, string $expected): void
    {
        $price = Price::create($input);

        self::assertSame($expected, $price->value());
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidPriceProvider(): Iterator
    {
        yield 'zero' => [
            'input'           => '0',
            'expectedMessage' => 'Price must be a positive number.',
        ];

        yield 'zero with decimals' => [
            'input'           => '0.00',
            'expectedMessage' => 'Price must be a positive number.',
        ];

        yield 'zero integer without decimals' => [
            'input'           => '00',
            'expectedMessage' => 'Price must be a positive number.',
        ];

        yield 'negative integer without decimals' => [
            'input'           => '-1',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'negative' => [
            'input'           => '-10',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'three decimals' => [
            'input'           => '10.123',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'non numeric' => [
            'input'           => 'abc',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'empty' => [
            'input'           => '',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'trailing dot' => [
            'input'           => '10.',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'leading dot' => [
            'input'           => '.5',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'plus sign prefix' => [
            'input'           => '+10',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'leading whitespace' => [
            'input'           => ' 10',
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];
    }

    /**
     * Ověřuje, že Price::create() odmítá neplatné hodnoty.
     * Vstup: řetězce z invalidPriceProvider (např. "0", "abc", "10.123").
     * Důvod: cena musí být kladné desetinné číslo s nejvýše 2 desetinnými místy.
     */
    #[DataProvider('invalidPriceProvider')]
    public function testCreateRejectsInvalidPrices(string $input, string $expectedMessage): void
    {
        try
        {
            Price::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%s".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
