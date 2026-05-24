<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Value;

use App\Orders\Dto\ProductDto;
use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Value\OrderItem;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderItemTest extends TestCase
{
    /** @return Iterator<string, array{product: ProductDto}> */
    public static function validProductDtoProvider(): Iterator
    {
        yield 'valid product' => [
            'product' => self::createProductDto(),
        ];
    }

    /**
     * Ověřuje, že OrderItem::create() vytvoří value objekt z platného ProductDto.
     * Vstup: ProductDto z validProductDtoProvider.
     * Důvod: položky objednávky se skládají z validovaných dat produktu předaných v požadavku.
     */
    #[DataProvider('validProductDtoProvider')]
    public function testHappyFlowCreateFromValidProductDto(ProductDto $product): void
    {
        $orderItem = OrderItem::create($product);

        self::assertSame('product-1', $orderItem->productId()->value());
        self::assertSame('Product title', $orderItem->title()->value());
        self::assertSame('19.99', $orderItem->price()->value());
        self::assertSame(2, $orderItem->quantity()->value());
    }

    /** @return Iterator<string, array{product: ProductDto, expectedMessage: string}> */
    public static function invalidOrderItemProvider(): Iterator
    {
        yield 'blank product id' => [
            'product'         => self::createProductDto(id: '   '),
            'expectedMessage' => 'Product ID must not be blank.',
        ];

        yield 'blank title' => [
            'product'         => self::createProductDto(title: ''),
            'expectedMessage' => 'Product title must not be blank.',
        ];

        yield 'zero price' => [
            'product'         => self::createProductDto(price: '0'),
            'expectedMessage' => 'Price must be a positive number.',
        ];

        yield 'invalid price format' => [
            'product'         => self::createProductDto(price: 'abc'),
            'expectedMessage' => 'Price must be a positive decimal with up to 2 decimal places.',
        ];

        yield 'zero quantity' => [
            'product'         => self::createProductDto(quantity: 0),
            'expectedMessage' => 'Quantity must be a positive integer.',
        ];

        yield 'negative quantity' => [
            'product'         => self::createProductDto(quantity: -1),
            'expectedMessage' => 'Quantity must be a positive integer.',
        ];

        yield 'quantity exceeds maximum' => [
            'product'         => self::createProductDto(quantity: 100_001),
            'expectedMessage' => 'Quantity must not exceed 100000.',
        ];
    }

    /**
     * Ověřuje, že OrderItem::create() propaguje InvalidValueException z vnořených value objektů.
     * Vstup: neplatný ProductDto z invalidOrderItemProvider.
     * Důvod: neplatná data produktu musí selhat při vytváření doménového value objektu, ne projít tiše.
     */
    #[DataProvider('invalidOrderItemProvider')]
    public function testCreateRejectsInvalidProductDto(ProductDto $product, string $expectedMessage): void
    {
        try
        {
            OrderItem::create($product);
            self::fail(sprintf('Expected InvalidValueException for product id "%s".', $product->id));
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame($expectedMessage, $exception->getMessage());
        }
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
