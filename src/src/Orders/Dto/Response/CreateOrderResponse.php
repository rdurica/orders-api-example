<?php

declare(strict_types=1);

namespace App\Orders\Dto\Response;

final class CreateOrderResponse
{
    public string $status = 'created';

    public string $message = 'Order was created successfully.';
}
