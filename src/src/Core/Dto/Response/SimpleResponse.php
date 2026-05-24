<?php

declare(strict_types=1);

namespace App\Core\Dto\Response;

final readonly class SimpleResponse
{
    public function __construct(public string $message, public bool $success = true)
    {
    }
}
