<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\OrderId;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderIdTest extends TestCase
{
    /** @return Iterator<string, array{input: string, expected: string}> */
    public static function validOrderIdProvider(): Iterator
    {
        yield 'standard order id' => [
            'input'    => 'order-1',
            'expected' => 'order-1',
        ];

        yield 'maximum length boundary' => [
            'input'    => str_repeat('a', 64),
            'expected' => str_repeat('a', 64),
        ];

        yield 'surrounding spaces trimmed' => [
            'input'    => '    order-1   ',
            'expected' => 'order-1',
        ];

        yield 'surrounding spaces with internal spaces preserved' => [
            'input'    => '  order with spaces  ',
            'expected' => 'order with spaces',
        ];

        yield 'surrounding spaces trimmed to maximum length' => [
            'input'    => ' ' . str_repeat('a', 64) . ' ',
            'expected' => str_repeat('a', 64),
        ];

        yield 'with plus sign' => [
            'input'    => 'order+1',
            'expected' => 'order+1',
        ];

        yield 'with backslash' => [
            'input'    => 'order\\path',
            'expected' => 'order\\path',
        ];

        yield 'explicit backslash only' => [
            'input'    => '\\',
            'expected' => '\\',
        ];

        yield 'explicit double backslash' => [
            'input'    => '\\\\',
            'expected' => '\\\\',
        ];

        yield 'with plus and backslash' => [
            'input'    => 'order+with\\slash',
            'expected' => 'order+with\\slash',
        ];

        yield 'with underscore and dot' => [
            'input'    => 'order_1.test',
            'expected' => 'order_1.test',
        ];

        yield 'numeric only' => [
            'input'    => '1234567890',
            'expected' => '1234567890',
        ];

        yield 'chinese characters' => [
            'input'    => '订单-1',
            'expected' => '订单-1',
        ];

        yield 'literal nbsp entity' => [
            'input'    => 'order&nbsp;1',
            'expected' => 'order&nbsp;1',
        ];

        yield 'surrounding nbsp not trimmed' => [
            'input'    => "\u{00A0}order-1\u{00A0}",
            'expected' => "\u{00A0}order-1\u{00A0}",
        ];
    }

    /**
     * Ověřuje, že OrderId::create() přijímá platné neprázdné hodnoty v rámci maximální délky.
     *
     * Vstup: páry řetězců z validOrderIdProvider.
     * Důvod: ID objednávky musí být neprázdný řetězec o délce nejvýše 64 znaků.
     */
    #[DataProvider('validOrderIdProvider')]
    public function testHappyFlowCreateAcceptsValidOrderIds(string $input, string $expected): void
    {
        $orderId = OrderId::create($input);

        self::assertSame($expected, $orderId->value());
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidBlankOrderIdProvider(): Iterator
    {
        yield 'empty string' => [
            'input'           => '',
            'expectedMessage' => 'Order ID must not be blank.',
        ];

        yield 'whitespace only' => [
            'input'           => '   ',
            'expectedMessage' => 'Order ID must not be blank.',
        ];

        yield 'tab and newline' => [
            'input'           => "\t\n",
            'expectedMessage' => 'Order ID must not be blank.',
        ];
    }

    /**
     * Ověřuje, že OrderId::create() odmítá prázdné hodnoty.
     * Vstup: řetězce z invalidBlankOrderIdProvider.
     * Důvod: ID objednávky nesmí být po oříznutí prázdné.
     */
    #[DataProvider('invalidBlankOrderIdProvider')]
    public function testCreateRejectsBlankOrderIds(string $input, string $expectedMessage): void
    {
        try
        {
            OrderId::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%s".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidMaxLengthOrderIdProvider(): Iterator
    {
        yield 'exceeds maximum by one' => [
            'input'           => str_repeat('a', 65),
            'expectedMessage' => 'Order ID must not exceed 64 characters.',
        ];
    }

    /**
     * Ověřuje, že OrderId::create() odmítá hodnoty přesahující maximální délku.
     * Vstup: řetězce z invalidMaxLengthOrderIdProvider.
     * Důvod: limit sloupce databáze musí být vynucen na doménové vrstvě.
     */
    #[DataProvider('invalidMaxLengthOrderIdProvider')]
    public function testCreateRejectsValueExceedingMaxLength(string $input, string $expectedMessage): void
    {
        try
        {
            OrderId::create($input);
            self::fail(sprintf('Expected InvalidValueException for input length %d.', strlen($input)));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
