<?php

declare(strict_types=1);

namespace App\Orders\Dto\Response;

final class UpdateDeliveryDateResponse
{
    public bool $success = true;

    public string $message = 'Order delivery date was updated successfully.';
}
