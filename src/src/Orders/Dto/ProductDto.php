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

    #[Assert\NotBlank]
    #[Assert\Positive]
    public float $price = 0.0;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $quantity = 0;
}
