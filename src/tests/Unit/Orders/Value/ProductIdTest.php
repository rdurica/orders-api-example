<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\ProductId;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
{
    /** @return Iterator<string, array{input: string, expected: string}> */
    public static function validProductIdProvider(): Iterator
    {
        yield 'standard product id' => [
            'input'    => 'product-1',
            'expected' => 'product-1',
        ];

        yield 'maximum length boundary' => [
            'input'    => str_repeat('a', 64),
            'expected' => str_repeat('a', 64),
        ];

        yield 'surrounding spaces trimmed' => [
            'input'    => '    product-1   ',
            'expected' => 'product-1',
        ];

        yield 'surrounding spaces with internal spaces preserved' => [
            'input'    => '  product with spaces  ',
            'expected' => 'product with spaces',
        ];

        yield 'surrounding spaces trimmed to maximum length' => [
            'input'    => ' ' . str_repeat('a', 64) . ' ',
            'expected' => str_repeat('a', 64),
        ];

        yield 'chinese characters' => [
            'input'    => '产品-1',
            'expected' => '产品-1',
        ];
    }

    /**
     * Ověřuje, že ProductId::create() přijímá platné neprázdné hodnoty v rámci maximální délky.
     *
     * Vstup: páry řetězců z validProductIdProvider.
     * Důvod: ID produktu musí být neprázdný řetězec o délce nejvýše 64 znaků.
     */
    #[DataProvider('validProductIdProvider')]
    public function testHappyFlowCreateAcceptsValidProductIds(string $input, string $expected): void
    {
        $productId = ProductId::create($input);

        self::assertSame($expected, $productId->value());
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidBlankProductIdProvider(): Iterator
    {
        yield 'empty string' => [
            'input'           => '',
            'expectedMessage' => 'Product ID must not be blank.',
        ];

        yield 'whitespace only' => [
            'input'           => '   ',
            'expectedMessage' => 'Product ID must not be blank.',
        ];

        yield 'tab and newline' => [
            'input'           => "\t\n",
            'expectedMessage' => 'Product ID must not be blank.',
        ];
    }

    /**
     * Ověřuje, že ProductId::create() odmítá prázdné hodnoty.
     *
     * Vstup: řetězce z invalidBlankProductIdProvider.
     * Důvod: ID produktu nesmí být po oříznutí prázdné.
     */
    #[DataProvider('invalidBlankProductIdProvider')]
    public function testCreateRejectsBlankProductIds(string $input, string $expectedMessage): void
    {
        try
        {
            ProductId::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%s".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidMaxLengthProductIdProvider(): Iterator
    {
        yield 'exceeds maximum by one' => [
            'input'           => str_repeat('a', 65),
            'expectedMessage' => 'Product ID must not exceed 64 characters.',
        ];
    }

    /**
     * Ověřuje, že ProductId::create() odmítá hodnoty přesahující maximální délku.
     *
     * Vstup: řetězce z invalidMaxLengthProductIdProvider.
     * Důvod: limit sloupce databáze musí být vynucen na doménové vrstvě.
     */
    #[DataProvider('invalidMaxLengthProductIdProvider')]
    public function testCreateRejectsValueExceedingMaxLength(string $input, string $expectedMessage): void
    {
        try
        {
            ProductId::create($input);
            self::fail(sprintf('Expected InvalidValueException for input length %d.', strlen($input)));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
