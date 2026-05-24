<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Entity\Order;

use App\Orders\Dto\ProductDto;
use App\Orders\Entity\Order;
use App\Orders\Entity\OrderItem as OrderItemEntity;
use App\Orders\Value\OrderItem;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class HasSameItemsAsTest extends TestCase
{
    /**
     * Ověřuje, že Order::hasSameItemsAs() ignoruje pořadí položek díky sortování signatur.
     * Vstup: entita se dvěma položkami, request se stejnými položkami v obráceném pořadí.
     * Důvod: idempotence musí porovnávat množinu položek, ne pořadí v requestu.
     */
    public function testIgnoresItemOrder(): void
    {
        $order = self::createOrderWithItems([
            ['product-1', 'Product A', '10.00', 1],
            ['product-2', 'Product B', '20.00', 2],
        ]);

        $requestItems = [
            self::createOrderItemVo('product-2', 'Product B', '20.00', 2),
            self::createOrderItemVo('product-1', 'Product A', '10.00', 1),
        ];

        self::assertTrue($order->hasSameItemsAs($requestItems));
    }

    /**
     * Ověřuje, že Order::hasSameItemsAs() vrátí false při rozdílném počtu položek.
     * Vstup: entita s jednou položkou, request se dvěma.
     * Důvod: metoda musí ukončit porovnání hned po kontrole count(), ne až na úrovni signatur.
     */
    public function testReturnsFalseWhenItemCountDiffers(): void
    {
        $order = self::createOrderWithItems([
            ['product-1', 'Product A', '10.00', 1],
        ]);

        $requestItems = [
            self::createOrderItemVo('product-1', 'Product A', '10.00', 1),
            self::createOrderItemVo('product-2', 'Product B', '20.00', 1),
        ];

        self::assertFalse($order->hasSameItemsAs($requestItems));
    }

    /**
     * Ověřuje, že Order::hasSameItemsAs() vrátí false, když se liší signatura alespoň jedné položky.
     * Vstup: stejný product id, jiná cena — detail signatury testuje OrderItemTest.
     * Důvod: Order musí správně propojit entity signatury s VO signaturami z requestu.
     */
    public function testReturnsFalseWhenItemSignaturesDiffer(): void
    {
        $order = self::createOrderWithItems([
            ['product-1', 'Product A', '10.00', 1],
        ]);

        $requestItems = [
            self::createOrderItemVo('product-1', 'Product A', '11.00', 1),
        ];

        self::assertFalse($order->hasSameItemsAs($requestItems));
    }

    /**
     * @param list<array{string, string, string, int}> $items
     */
    private static function createOrderWithItems(array $items): Order
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable('2026-05-24 12:00:00'));

        foreach ($items as [$productId, $title, $price, $quantity])
        {
            $order->addItem(new OrderItemEntity($productId, $title, $price, $quantity));
        }

        return $order;
    }

    private static function createOrderItemVo(string $productId, string $title, string $price, int $quantity): OrderItem
    {
        $product = new ProductDto();
        $product->id = $productId;
        $product->title = $title;
        $product->price = $price;
        $product->quantity = $quantity;

        return OrderItem::create($product);
    }
}
