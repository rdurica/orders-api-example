<?php

declare(strict_types=1);

namespace App\Orders\Dto\Request;

use App\Core\Values\IRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderRequest implements IRequestDto
{
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    public string $partnerId = '';

    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    public string $orderId = '';

    #[Assert\DateTime(format: \DateTimeInterface::RFC3339)]
    #[Assert\NotBlank]
    public string $expectedDeliveryDate = '';

    /** @var list<array{id: string, title: string, price: float|int, quantity: int}> */
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'id' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 64),
                ],
                'title' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
                'price' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
                'quantity' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ],
            allowExtraFields: false,
        ),
    ])]
    #[Assert\Count(min: 1)]
    #[Assert\NotBlank]
    public array $products = [];
}
