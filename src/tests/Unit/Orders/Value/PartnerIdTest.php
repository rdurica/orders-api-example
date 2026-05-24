<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\PartnerId;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PartnerIdTest extends TestCase
{
    /** @return Iterator<string, array{input: string, expected: string}> */
    public static function validPartnerIdProvider(): Iterator
    {
        yield 'standard partner id' => [
            'input'    => 'partner-1',
            'expected' => 'partner-1',
        ];

        yield 'maximum length boundary' => [
            'input'    => str_repeat('a', 64),
            'expected' => str_repeat('a', 64),
        ];

        yield 'surrounding spaces trimmed' => [
            'input'    => '    partner-1   ',
            'expected' => 'partner-1',
        ];

        yield 'surrounding spaces with internal spaces preserved' => [
            'input'    => '  partner with spaces  ',
            'expected' => 'partner with spaces',
        ];

        yield 'surrounding spaces trimmed to maximum length' => [
            'input'    => ' ' . str_repeat('a', 64) . ' ',
            'expected' => str_repeat('a', 64),
        ];

        yield 'chinese characters' => [
            'input'    => '伙伴-1',
            'expected' => '伙伴-1',
        ];
    }

    /**
     * Ověřuje, že PartnerId::create() přijímá platné neprázdné hodnoty v rámci maximální délky.
     * Vstup: páry řetězců z validPartnerIdProvider.
     * Důvod: ID partnera musí být neprázdný řetězec o délce nejvýše 64 znaků.
     */
    #[DataProvider('validPartnerIdProvider')]
    public function testHappyFlowCreateAcceptsValidPartnerIds(string $input, string $expected): void
    {
        $partnerId = PartnerId::create($input);

        self::assertSame($expected, $partnerId->value());
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidBlankPartnerIdProvider(): Iterator
    {
        yield 'empty string' => [
            'input'           => '',
            'expectedMessage' => 'Partner ID must not be blank.',
        ];

        yield 'whitespace only' => [
            'input'           => '   ',
            'expectedMessage' => 'Partner ID must not be blank.',
        ];

        yield 'tab and newline' => [
            'input'           => "\t\n",
            'expectedMessage' => 'Partner ID must not be blank.',
        ];
    }

    /**
     * Ověřuje, že PartnerId::create() odmítá prázdné hodnoty.
     * Vstup: řetězce z invalidBlankPartnerIdProvider.
     * Důvod: ID partnera nesmí být po oříznutí prázdné.
     */
    #[DataProvider('invalidBlankPartnerIdProvider')]
    public function testCreateRejectsBlankPartnerIds(string $input, string $expectedMessage): void
    {
        try
        {
            PartnerId::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%s".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /** @return Iterator<string, array{input: string, expectedMessage: string}> */
    public static function invalidMaxLengthPartnerIdProvider(): Iterator
    {
        yield 'exceeds maximum by one' => [
            'input'           => str_repeat('a', 65),
            'expectedMessage' => 'Partner ID must not exceed 64 characters.',
        ];
    }

    /**
     * Ověřuje, že PartnerId::create() odmítá hodnoty přesahující maximální délku.
     * Vstup: řetězce z invalidMaxLengthPartnerIdProvider.
     * Důvod: limit sloupce databáze musí být vynucen na doménové vrstvě.
     */
    #[DataProvider('invalidMaxLengthPartnerIdProvider')]
    public function testCreateRejectsValueExceedingMaxLength(string $input, string $expectedMessage): void
    {
        try
        {
            PartnerId::create($input);
            self::fail(sprintf('Expected InvalidValueException for input length %d.', strlen($input)));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
