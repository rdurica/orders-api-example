<?php

declare(strict_types=1);

namespace App\Orders\Controller;

use App\Core\Controller\ApiController;
use App\Core\Http\RequestDtoFactory;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use App\Orders\Handler\UpdateDeliveryDateHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateDeliveryDateController extends ApiController
{
    public function __construct(private readonly UpdateDeliveryDateHandler $handler, private readonly RequestDtoFactory $requestDtoFactory)
    {
    }

    #[Route('/v1/orders', name: 'orders_update_delivery_date', methods: ['PATCH'])]
    public function __invoke(Request $request): JsonResponse
    {
        $requestDto = $this->requestDtoFactory->create($request->getContent(), UpdateDeliveryDateRequest::class);
        $responseDto = ($this->handler)($requestDto);

        return $this->createResponse($responseDto)->setStatusCode(Response::HTTP_OK);
    }
}
