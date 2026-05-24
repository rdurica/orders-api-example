<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\ProductTitle;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductTitleTest extends TestCase
{
    /** @return Iterator<string, array{input: string, expected: string}> */
    public static function validProductTitleProvider(): Iterator
    {
        yield 'standard product title' => [
            'input'    => 'Product title',
            'expected' => 'Product title',
        ];

        yield 'maximum length boundary' => [
            'input'    => str_repeat('a', 255),
            'expected' => str_repeat('a', 255),
        ];

        yield 'unicode characters' => [
            'input'    => 'Produkt č. 1',
            'expected' => 'Produkt č. 1',
        ];

        yield 'surrounding spaces trimmed' => [
            'input'    => '    Product title   ',
            'expected' => 'Product title',
        ];

        yield 'surrounding spaces with unicode trimmed' => [
            'input'    => '  Produkt č. 1  ',
            'expected' => 'Produkt č. 1',
        ];

        yield 'surrounding spaces trimmed to maximum length' => [
            'input'    => ' ' . str_repeat('a', 255) . ' ',
            'expected' => str_repeat('a', 255),
        ];

        yield 'chinese characters' => [
            'input'    => '产品名称',
            'expected' => '产品名称',
        ];

        yield 'chinese characters with surrounding spaces trimmed' => [
            'input'    => '  产品名称  ',
            'expected' => '产品名称',
        ];

        yield 'literal nbsp entity' => [
            'input'    => 'Product&nbsp;title',
            'expected' => 'Product&nbsp;title',
        ];

        yield 'non breaking space inside text' => [
            'input'    => 'Product' . "\u{00A0}" . 'title',
            'expected' => 'Product' . "\u{00A0}" . 'title',
        ];

        yield 'thin space inside text' => [
            'input'    => 'Product' . "\u{2009}" . 'title',
            'expected' => 'Product' . "\u{2009}" . 'title',
        ];

        yield 'em space inside text' => [
            'input'    => 'Product' . "\u{2003}" . 'title',
            'expected' => 'Product' . "\u{2003}" . 'title',
        ];

        yield 'surrounding nbsp not trimmed' => [
            'input'    => "\u{00A0}Product title\u{00A0}",
            'expected' => "\u{00A0}Product title\u{00A0}",
        ];
    }

    /**
     * Ověřuje, že ProductTitle::create() přijímá platné neprázdné hodnoty v rámci maximální délky.
     *
     * Vstup: páry řetězců z validProductTitleProvider.
     * Důvod: název produktu musí být neprázdný řetězec o délce nejvýše 255 znaků.
     */
    #[DataProvider('validProductTitleProvider')]
    public function testHappyFlowCreateAcceptsValidProductTitles(string $input, string $expected): void
    {
        $productTitle = ProductTitle::create($input);

        self::assertSame($expected, $productTitle->value());
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidBlankProductTitleProvider(): Iterator
    {
        yield 'empty string' => [
            'input'           => '',
            'expectedMessage' => 'Product title must not be blank.',
        ];

        yield 'whitespace only' => [
            'input'           => '   ',
            'expectedMessage' => 'Product title must not be blank.',
        ];

        yield 'tab and newline' => [
            'input'           => "\t\n",
            'expectedMessage' => 'Product title must not be blank.',
        ];
    }

    /**
     * Ověřuje, že ProductTitle::create() odmítá prázdné hodnoty.
     *
     * Vstup: řetězce z invalidBlankProductTitleProvider.
     * Důvod: název produktu nesmí být po oříznutí prázdný.
     */
    #[DataProvider('invalidBlankProductTitleProvider')]
    public function testCreateRejectsBlankProductTitles(string $input, string $expectedMessage): void
    {
        try
        {
            ProductTitle::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%s".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidMaxLengthProductTitleProvider(): Iterator
    {
        yield 'exceeds maximum by one' => [
            'input'           => str_repeat('a', 256),
            'expectedMessage' => 'Product title must not exceed 255 characters.',
        ];
    }

    /**
     * Ověřuje, že ProductTitle::create() odmítá hodnoty přesahující maximální délku.
     *
     * Vstup: řetězce z invalidMaxLengthProductTitleProvider.
     * Důvod: limit sloupce databáze musí být vynucen na doménové vrstvě.
     */
    #[DataProvider('invalidMaxLengthProductTitleProvider')]
    public function testCreateRejectsValueExceedingMaxLength(string $input, string $expectedMessage): void
    {
        try
        {
            ProductTitle::create($input);
            self::fail(sprintf('Expected InvalidValueException for input length %d.', strlen($input)));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
