<?php

declare(strict_types=1);

namespace App\Orders\Dto\Request;

use App\Core\Values\IRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateDeliveryDateRequest implements IRequestDto
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
}
