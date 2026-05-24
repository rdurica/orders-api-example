<?php

declare(strict_types=1);

namespace App\Orders\Controller;

use App\Core\Controller\ApiController;
use App\Core\Exception\Api\InvalidContentException;
use App\Core\Exception\Api\InvalidDataException;
use App\Core\Exception\Api\UnexpectedException;
use App\Core\Http\RequestDtoFactory;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Exception\Api\OrderAlreadyExistsException as OrderAlreadyExistsApiException;
use App\Orders\Exception\Domain\DomainException;
use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Exception\Domain\OrderAlreadyExistsException;
use App\Orders\Handler\CreateOrderHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final class CreateOrderController extends ApiController
{
    public function __construct(private CreateOrderHandler $handler, private RequestDtoFactory $requestDtoFactory)
    {
    }

    /**
     * @throws InvalidContentException
     * @throws InvalidDataException
     * @throws OrderAlreadyExistsApiException
     * @throws UnexpectedException
     * @throws ExceptionInterface
     */
    #[Route('/v1/orders', name: 'orders_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $requestDto = $this->requestDtoFactory->create($request->getContent(), CreateOrderRequest::class);

        try
        {
            $responseDto = ($this->handler)($requestDto);
        }
        catch (InvalidValueException)
        {
            throw new InvalidDataException();
        }
        catch (OrderAlreadyExistsException)
        {
            throw new OrderAlreadyExistsApiException();
        }
        catch (DomainException)
        {
            throw new UnexpectedException();
        }

        return $this->createResponse($responseDto)->setStatusCode(Response::HTTP_CREATED);
    }
}
