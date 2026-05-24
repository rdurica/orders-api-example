<?php

declare(strict_types=1);

namespace App\Orders\Dto\Request;

use App\Core\Dto\Request\IRequestDto;
use App\Orders\Dto\ProductDto;
use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderRequest implements IRequestDto
{
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    public string $partnerId = '';

    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    public string $orderId = '';

    #[Assert\DateTime(format: DateTimeInterface::RFC3339)]
    #[Assert\NotBlank]
    public string $expectedDeliveryDate = '';

    /** @var list<ProductDto> */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    #[Assert\NotBlank]
    public array $products = [];
}
