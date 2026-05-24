<?php

declare(strict_types=1);

namespace App\Orders\Value;

use App\Orders\Dto\ProductDto;
use App\Orders\Entity\OrderItem as OrderItemEntity;

final readonly class OrderItem
{
    private function __construct(private ProductId $productId, private ProductTitle $title, private Price $price, private Quantity $quantity)
    {
    }

    public static function create(ProductDto $product): self
    {
        return new self(
            ProductId::create($product->id),
            ProductTitle::create($product->title),
            Price::create($product->price),
            Quantity::create($product->quantity),
        );
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function title(): ProductTitle
    {
        return $this->title;
    }

    public function price(): Price
    {
        return $this->price;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function matches(OrderItemEntity $orderItemEntity): bool
    {
        return $this->productId->value() === $orderItemEntity->productId()
            && $this->title->value() === $orderItemEntity->title()
            && $this->price->value() === $orderItemEntity->price()
            && $this->quantity->value() === $orderItemEntity->quantity();
    }
}
