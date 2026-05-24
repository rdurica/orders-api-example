<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Entity\Order;

use App\Orders\Entity\Order;
use App\Orders\Value\ExpectedDeliveryDate;
use DateMalformedStringException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class HasSameExpectedDeliveryDateAsTest extends TestCase
{
    /**
     * Ověřuje, že Order::hasSameExpectedDeliveryDateAs() vrátí true pro shodný UTC okamžik.
     * Vstup: uložené "2026-05-24 12:00:00", ExpectedDeliveryDate z RFC3339 se stejným UTC instantem.
     * Důvod: idempotentní PATCH musí považovat ekvivalentní RFC3339 reprezentace za shodné datum doručení.
     *
     * @throws DateMalformedStringException
     */
    public function testReturnsTrueWhenFormattedDateTimeMatches(): void
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable('2026-05-24 12:00:00'));
        $deliveryDate = ExpectedDeliveryDate::create('2026-05-24T12:00:00+00:00');

        self::assertTrue($order->hasSameExpectedDeliveryDateAs($deliveryDate));
    }

    /**
     * Ověřuje, že Order::hasSameExpectedDeliveryDateAs() porovnává UTC instant, ne wall clock z format().
     * Vstup: entita "2026-05-24 12:00:00", RFC3339 "2026-05-24T14:00:00+02:00" (stejný UTC okamžik).
     * Důvod: stejný okamžik v jiném offsetu musí být pro idempotenci považován za shodný.
     *
     * @throws DateMalformedStringException
     */
    public function testReturnsTrueWhenUtcInstantMatchesDespiteDifferentOffset(): void
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable('2026-05-24 12:00:00'));
        $deliveryDate = ExpectedDeliveryDate::create('2026-05-24T14:00:00+02:00');

        self::assertTrue($order->hasSameExpectedDeliveryDateAs($deliveryDate));
    }
}
