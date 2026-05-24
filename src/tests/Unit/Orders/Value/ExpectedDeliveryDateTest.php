<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\ExpectedDeliveryDate;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExpectedDeliveryDateTest extends TestCase
{
    /** @return Iterator<string, array{input: string, expected: string}> */
    public static function validExpectedDeliveryDateProvider(): Iterator
    {
        yield 'utc with zero minutes and seconds' => [
            'input'    => '2026-05-24T12:00:00+00:00',
            'expected' => '2026-05-24 12:00:00',
        ];

        yield 'positive offset +02:00 with minutes and seconds' => [
            'input'    => '2026-06-15T14:30:45+02:00',
            'expected' => '2026-06-15 14:30:45',
        ];

        yield 'positive offset +05:30' => [
            'input'    => '2026-01-01T08:15:30+05:30',
            'expected' => '2026-01-01 08:15:30',
        ];

        yield 'end of year with non-zero time' => [
            'input'    => '2026-12-31T23:59:59+00:00',
            'expected' => '2026-12-31 23:59:59',
        ];

        yield 'invalid december day overflows to next year' => [
            'input'    => '2026-12-32T12:00:00+00:00',
            'expected' => '2027-01-01 12:00:00',
        ];

        yield 'positive offset +14:00 overflows december thirty first to january first' => [
            'input'    => '2026-12-31T24:00:00+14:00',
            'expected' => '2027-01-01 00:00:00',
        ];

        yield 'negative offset -12:00 overflows january first to december thirty first previous year' => [
            'input'    => '2027-01-00T00:00:00-12:00',
            'expected' => '2026-12-31 00:00:00',
        ];

        yield 'leap day with positive offset +01:00' => [
            'input'    => '2028-02-29T10:20:30+01:00',
            'expected' => '2028-02-29 10:20:30',
        ];

        yield 'positive offset +14:00 edge case' => [
            'input'    => '2026-03-10T09:05:07+14:00',
            'expected' => '2026-03-10 09:05:07',
        ];

        yield 'utc Z suffix' => [
            'input'    => '2026-05-24T12:00:00Z',
            'expected' => '2026-05-24 12:00:00',
        ];
    }

    /**
     * Ověřuje, že ExpectedDeliveryDate::create() přijímá platné řetězce data a času ve formátu RFC3339.
     * Vstup: páry řetězců z validExpectedDeliveryDateProvider (různá data, minuty, vteřiny, timezone offsety s +).
     * Důvod: datum doručení musí být parsovatelné jako RFC3339 pro konzistentní ukládání a porovnávání.
     */
    #[DataProvider('validExpectedDeliveryDateProvider')]
    public function testHappyFlowCreateAcceptsValidRfc3339Dates(string $input, string $expected): void
    {
        $date = ExpectedDeliveryDate::create($input);

        self::assertSame($expected, $date->value()->format('Y-m-d H:i:s'));
    }

    /** @return Iterator<string, array{input: string}> */
    public static function invalidExpectedDeliveryDateProvider(): Iterator
    {
        yield 'empty string' => ['input' => ''];
        yield 'whitespace only' => ['input' => '   '];
        yield 'date only' => ['input' => '2026-05-24'];
        yield 'time only' => ['input' => '12:00:00'];
        yield 'arbitrary text' => ['input' => 'not-a-date'];
        yield 'iso datetime without timezone' => ['input' => '2026-05-24T12:00:00'];
        yield 'space separator' => ['input' => '2026-05-24 12:00:00+00:00'];
        yield 'localized date with dot' => ['input' => '24.05.2026'];
        yield 'localized date with slash dd/mm/yyyy' => ['input' => '24/05/2026'];
        yield 'localized date with slash mm/dd/yyyy' => ['input' => '05/24/2026'];
        yield 'date with slash yyyy/mm/dd' => ['input' => '2026/05/24'];
        yield 'slash date with time' => ['input' => '24/05/2026 12:00:00'];
        yield 'numeric timestamp' => ['input' => '1716552000'];
    }

    /**
     * Ověřuje, že ExpectedDeliveryDate::create() odmítá neplatné formáty data a času.
     * Vstup: řetězce z invalidExpectedDeliveryDateProvider (prázdný text, jen datum, jen čas, lomítkové formáty, libovolný text).
     * Důvod: v doménovém modelu jsou povoleny pouze platná data a časy RFC3339.
     */
    #[DataProvider('invalidExpectedDeliveryDateProvider')]
    public function testCreateRejectsInvalidExpectedDeliveryDates(string $input): void
    {
        try
        {
            ExpectedDeliveryDate::create($input);
            self::fail(sprintf('Expected InvalidValueException for input "%s".', $input));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame('Expected delivery date must be a valid RFC3339 datetime.', $exception->getMessage());
        }
    }
}
