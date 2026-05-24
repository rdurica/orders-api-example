<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Entity;

use App\Orders\Entity\OrderItem;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderItemTest extends TestCase
{
    /** @return Iterator<string, array{productId: string, title: string, price: string, quantity: int}> */
    public static function signatureValuesProvider(): Iterator
    {
        yield 'standard values' => [
            'productId' => 'product-1',
            'title'     => 'Product title',
            'price'     => '19.90',
            'quantity'  => 2,
        ];

        yield 'unicode title' => [
            'productId' => 'product-1',
            'title'     => 'Produkt č. 1',
            'price'     => '10.00',
            'quantity'  => 1,
        ];

        yield 'minimum quantity' => [
            'productId' => 'p',
            'title'     => 't',
            'price'     => '0.01',
            'quantity'  => 1,
        ];
    }

    /**
     * Ověřuje, že OrderItem::signature() vrátí stejnou hodnotu jako signatureFromValues() pro shodná data.
     * Vstup: hodnoty polí ze signatureValuesProvider.
     * Důvod: porovnávání položek pro idempotenci spoléhá na stabilní signaturu odvozenou ze všech polí.
     */
    #[DataProvider('signatureValuesProvider')]
    public function testHappyFlowSignatureMatchesSignatureFromValues(string $productId, string $title, string $price, int $quantity): void
    {
        $entity = new OrderItem($productId, $title, $price, $quantity);

        self::assertSame(
            OrderItem::signatureFromValues($productId, $title, $price, $quantity),
            $entity->signature(),
        );
    }

    /** @return Iterator<string, array{productId: string, title: string, price: string, quantity: int, expected: string}> */
    public static function signatureFromValuesProvider(): Iterator
    {
        yield 'standard values' => [
            'productId' => 'product-1',
            'title'     => 'Product title',
            'price'     => '19.90',
            'quantity'  => 2,
            'expected'  => 'product-1' . "\0" . 'Product title' . "\0" . '19.90' . "\0" . '2',
        ];

        yield 'single character fields' => [
            'productId' => 'a',
            'title'     => 'b',
            'price'     => '1.00',
            'quantity'  => 1,
            'expected'  => 'a' . "\0" . 'b' . "\0" . '1.00' . "\0" . '1',
        ];

        yield 'quantity cast to string' => [
            'productId' => 'product-1',
            'title'     => 'Title',
            'price'     => '10.50',
            'quantity'  => 100,
            'expected'  => 'product-1' . "\0" . 'Title' . "\0" . '10.50' . "\0" . '100',
        ];
    }

    /**
     * Ověřuje, že OrderItem::signatureFromValues() používá oddělovače null-byte mezi poli.
     * Vstup: hodnoty a očekávaný řetězec ze signatureFromValuesProvider.
     * Důvod: jednoznačné hranice polí zabraňují nejednoznačné konkatenaci při porovnávání signatur.
     */
    #[DataProvider('signatureFromValuesProvider')]
    public function testHappyFlowSignatureFromValuesSeparatesFields(string $productId, string $title, string $price, int $quantity, string $expected): void
    {
        self::assertSame(
            $expected,
            OrderItem::signatureFromValues($productId, $title, $price, $quantity),
        );
    }

    /** @return Iterator<string, array{left: array{productId: string, title: string, price: string, quantity: int}, right: array{productId: string, title: string, price: string, quantity: int}}> */
    public static function nullByteSeparatorCollisionProvider(): Iterator
    {
        yield 'empty product id shifts title into same naive prefix' => [
            'left'  => ['productId' => '', 'title' => 'ab', 'price' => '1.00', 'quantity' => 1],
            'right' => ['productId' => 'a', 'title' => 'b', 'price' => '1.00', 'quantity' => 1],
        ];

        yield 'title moved to product id without separator ambiguity' => [
            'left'  => ['productId' => 'ab', 'title' => '', 'price' => '1.00', 'quantity' => 1],
            'right' => ['productId' => 'a', 'title' => 'b', 'price' => '1.00', 'quantity' => 1],
        ];
    }

    /**
     * Ověřuje důvod volby "\0" jako oddělovače — zabrání kolizi signatur při jiném rozdělení stejného textu.
     * Vstup: dvojice hodnot z nullByteSeparatorCollisionProvider, kde productId + title bez oddělovače dá "ab".
     * Důvod: při prosté konkatenaci by různé kombinace polí vytvořily stejnou signaturu; null-byte hranice pole zachová jednoznačnost.
     *
     * @param array{productId: string, title: string, price: string, quantity: int} $left
     * @param array{productId: string, title: string, price: string, quantity: int} $right
     */
    #[DataProvider('nullByteSeparatorCollisionProvider')]
    public function testSignatureFromValuesUsesNullByteToPreventFieldBoundaryCollision(array $left, array $right): void
    {
        self::assertSame(
            $left['productId'] . $left['title'],
            $right['productId'] . $right['title'],
            'Naive concatenation of productId and title must collide to demonstrate why separator is needed.',
        );

        self::assertNotSame(
            OrderItem::signatureFromValues(
                $left['productId'],
                $left['title'],
                $left['price'],
                $left['quantity'],
            ),
            OrderItem::signatureFromValues(
                $right['productId'],
                $right['title'],
                $right['price'],
                $right['quantity'],
            ),
        );
    }
}
