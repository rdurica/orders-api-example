<?php

declare(strict_types=1);

namespace App\Orders\Controller;

use App\Core\Controller\ApiController;
use App\Core\Http\RequestDtoFactory;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Handler\CreateOrderHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateOrderController extends ApiController
{
    public function __construct(private CreateOrderHandler $handler, private RequestDtoFactory $requestDtoFactory)
    {
    }

    #[Route('/v1/orders', name: 'orders_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $requestDto = $this->requestDtoFactory->create($request->getContent(), CreateOrderRequest::class);
        $responseDto = ($this->handler)($requestDto);

        return $this->createResponse($responseDto)->setStatusCode(Response::HTTP_CREATED);
    }
}
