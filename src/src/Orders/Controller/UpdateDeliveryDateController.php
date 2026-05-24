<?php

declare(strict_types=1);

namespace App\Orders\Controller;

use App\Core\Controller\ApiController;
use App\Core\Exception\Api\InvalidContentException;
use App\Core\Exception\Api\UnexpectedException;
use App\Core\Http\RequestDtoFactory;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use App\Orders\Exception\Api\InvalidDateException;
use App\Orders\Exception\Api\OrderNotFoundException as OrderNotFoundApiException;
use App\Orders\Exception\Domain\DomainException;
use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Exception\Domain\OrderNotFoundException;
use App\Orders\Handler\UpdateDeliveryDateHandler;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final class UpdateDeliveryDateController extends ApiController
{
    public function __construct(private readonly UpdateDeliveryDateHandler $handler, private readonly RequestDtoFactory $requestDtoFactory)
    {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidContentException
     * @throws InvalidDateException
     * @throws OrderNotFoundApiException
     * @throws UnexpectedException
     * @throws DateMalformedStringException
     */
    #[Route('/v1/orders', name: 'orders_update_delivery_date', methods: ['PATCH'])]
    public function __invoke(Request $request): JsonResponse
    {
        $requestDto = $this->requestDtoFactory->create($request->getContent(), UpdateDeliveryDateRequest::class);

        try
        {
            $responseDto = ($this->handler)($requestDto);
        }
        catch (InvalidValueException)
        {
            throw new InvalidDateException();
        }
        catch (OrderNotFoundException)
        {
            throw new OrderNotFoundApiException();
        }
        catch (DomainException)
        {
            throw new UnexpectedException();
        }

        return $this->createResponse($responseDto)->setStatusCode(Response::HTTP_OK);
    }
}
