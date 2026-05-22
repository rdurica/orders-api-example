<?php

declare(strict_types=1);

namespace App\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends AbstractController
{
    protected function createResponse(mixed $obj): JsonResponse
    {
        return new JsonResponse($obj);
    }
}
