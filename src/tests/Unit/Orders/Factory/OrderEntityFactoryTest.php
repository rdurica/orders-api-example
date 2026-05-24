<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Factory;

use App\Orders\Dto\ProductDto;
use App\Orders\Factory\OrderEntityFactory;
use App\Orders\Value\ExpectedDeliveryDate;
use App\Orders\Value\OrderId;
use App\Orders\Value\OrderItem;
use App\Orders\Value\PartnerId;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderEntityFactoryTest extends TestCase
{
    /** @return Iterator<string, array{partnerId: string, orderId: string, deliveryDate: string, productId: string, title: string, price: string, quantity: int, expectedDeliveryDate: string}> */
    public static function createOrderProvider(): Iterator
    {
        yield 'standard order with single item' => [
            'partnerId'            => 'partner-1',
            'orderId'              => 'order-1',
            'deliveryDate'         => '2026-05-24T12:00:00+00:00',
            'productId'            => 'product-1',
            'title'                => 'Product title',
            'price'                => '19.99',
            'quantity'             => 2,
            'expectedDeliveryDate' => '2026-05-24 12:00:00',
        ];

        yield 'unicode title' => [
            'partnerId'            => 'partner-1',
            'orderId'              => 'order-1',
            'deliveryDate'         => '2026-05-24T12:00:00+00:00',
            'productId'            => 'product-1',
            'title'                => 'Produkt č. 1',
            'price'                => '10.00',
            'quantity'             => 1,
            'expectedDeliveryDate' => '2026-05-24 12:00:00',
        ];

        yield 'minimum quantity and price' => [
            'partnerId'            => 'p',
            'orderId'              => 'o',
            'deliveryDate'         => '2026-05-24T14:00:00+02:00',
            'productId'            => 'product-1',
            'title'                => 'Title',
            'price'                => '0.01',
            'quantity'             => 1,
            'expectedDeliveryDate' => '2026-05-24 14:00:00',
        ];
    }

    /**
     * Ověřuje, že OrderEntityFactory::create() sestaví graf entit objednávky z value objektů.
     * Vstup: PartnerId, OrderId, ExpectedDeliveryDate a jeden OrderItem z createOrderProvider.
     * Důvod: persistenční vrstva potřebuje správně namapovanou Order s přidruženými entitami OrderItem.
     */
    #[DataProvider('createOrderProvider')]
    public function testHappyFlowCreateBuildsOrderWithItems(
        string $partnerId,
        string $orderId,
        string $deliveryDate,
        string $productId,
        string $title,
        string $price,
        int $quantity,
        string $expectedDeliveryDate,
    ): void
    {
        $factory = new OrderEntityFactory();
        $order = $factory->create(
            PartnerId::create($partnerId),
            OrderId::create($orderId),
            ExpectedDeliveryDate::create($deliveryDate),
            [self::createOrderItemVo($productId, $title, $price, $quantity)],
        );

        self::assertSame($partnerId, $order->partnerId());
        self::assertSame($orderId, $order->orderId());
        self::assertSame($expectedDeliveryDate, $order->expectedDeliveryDate()->format('Y-m-d H:i:s'));
        self::assertCount(1, $order->items());

        $item = $order->items()->first();
        self::assertNotFalse($item);
        self::assertSame($productId, $item->productId());
        self::assertSame($title, $item->title());
        self::assertSame($price, $item->price());
        self::assertSame($quantity, $item->quantity());
    }

    /**
     * Ověřuje, že OrderEntityFactory::create() namapuje všechny položky z value objektů na entity.
     * Vstup: dvě položky s různými product id.
     * Důvod: factory iteruje přes pole OrderItem a každý prvek musí skončit jako OrderItem entita.
     */
    public function testHappyFlowCreateMapsAllItems(): void
    {
        $factory = new OrderEntityFactory();
        $order = $factory->create(
            PartnerId::create('partner-1'),
            OrderId::create('order-1'),
            ExpectedDeliveryDate::create('2026-05-24T12:00:00+00:00'),
            [
                self::createOrderItemVo('product-1', 'Product A', '10.00', 1),
                self::createOrderItemVo('product-2', 'Product B', '20.00', 2),
            ],
        );

        self::assertCount(2, $order->items());

        $itemsByProductId = [];

        foreach ($order->items() as $item)
        {
            $itemsByProductId[$item->productId()] = $item;
        }

        self::assertSame('Product A', $itemsByProductId['product-1']->title());
        self::assertSame('10.00', $itemsByProductId['product-1']->price());
        self::assertSame(1, $itemsByProductId['product-1']->quantity());
        self::assertSame('Product B', $itemsByProductId['product-2']->title());
        self::assertSame('20.00', $itemsByProductId['product-2']->price());
        self::assertSame(2, $itemsByProductId['product-2']->quantity());
    }

    private static function createOrderItemVo(string $productId, string $title, string $price, int $quantity): OrderItem
    {
        return OrderItem::create(self::createProductDto($productId, $title, $price, $quantity));
    }

    private static function createProductDto(string $id = 'product-1', string $title = 'Product title', string $price = '19.99', int $quantity = 2): ProductDto
    {
        $product = new ProductDto();
        $product->id = $id;
        $product->title = $title;
        $product->price = $price;
        $product->quantity = $quantity;

        return $product;
    }
}
