<?php

declare(strict_types=1);

namespace App\Orders\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductDto
{
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    public string $id = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    public string $title = '';

    #[Assert\GreaterThan('0')]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Price must be a positive decimal with up to 2 decimal places.')]
    #[Assert\Type('string')]
    public string $price = '';

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $quantity = 0;
}
