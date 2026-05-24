<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Validator;

use App\Core\Dto\Request\IRequestDto;
use App\Core\Exception\Api\InvalidContentException;
use App\Core\Validator\RequestValidator;
use App\Orders\Dto\ProductDto;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestValidatorTest extends TestCase
{
    /** @return Iterator<string, array{request: IRequestDto}> */
    public static function validRequestProvider(): Iterator
    {
        yield 'create order request' => [
            'request' => self::createCreateOrderRequestWithProduct(self::createValidProductDto()),
        ];

        yield 'update delivery date request' => [
            'request' => self::createValidUpdateDeliveryDateRequest(),
        ];
    }

    /**
     * Ověřuje, že RequestValidator::validate() projde u platných request DTO.
     * Vstup: validní DTO z validRequestProvider.
     * Důvod: platná data z API musí projít Symfony validací atributů bez výjimky.
     *
     * @throws InvalidContentException
     */
    #[DataProvider('validRequestProvider')]
    public function testHappyFlowValidateAcceptsValidRequest(IRequestDto $request): void
    {
        $requestValidator = self::createRequestValidator();

        $requestValidator->validate($request);

        self::addToAssertionCount(1);
    }

    /** @return Iterator<string, array{request: IRequestDto, expectedErrors: list<array{field: string, message: string}>}> */
    public static function invalidRequestProvider(): Iterator
    {
        yield 'empty create order request' => [
            'request'        => new CreateOrderRequest(),
            'expectedErrors' => [
                ['field' => 'partnerId', 'message' => 'This value should not be blank.'],
                ['field' => 'orderId', 'message' => 'This value should not be blank.'],
                ['field' => 'expectedDeliveryDate', 'message' => 'This value should not be blank.'],
                ['field' => 'products', 'message' => 'This collection should contain 1 element or more.'],
                ['field' => 'products', 'message' => 'This value should not be blank.'],
            ],
        ];

        yield 'invalid product quantity' => [
            'request'        => self::createCreateOrderRequestWithProduct(self::createValidProductDto(quantity: 0)),
            'expectedErrors' => [
                ['field' => 'products[0].quantity', 'message' => 'This value should be between 1 and 100000.'],
            ],
        ];

        yield 'invalid product price format' => [
            'request'        => self::createCreateOrderRequestWithProduct(self::createValidProductDto(price: 'abc')),
            'expectedErrors' => [
                ['field' => 'products[0].price', 'message' => 'Price must be a positive decimal with up to 2 decimal places.'],
            ],
        ];

        yield 'empty update delivery date request' => [
            'request'        => new UpdateDeliveryDateRequest(),
            'expectedErrors' => [
                ['field' => 'partnerId', 'message' => 'This value should not be blank.'],
                ['field' => 'orderId', 'message' => 'This value should not be blank.'],
                ['field' => 'expectedDeliveryDate', 'message' => 'This value should not be blank.'],
            ],
        ];
    }

    /**
     * Ověřuje, že RequestValidator::validate() vyhodí InvalidContentException s reálnými Symfony chybami.
     * Vstup: neplatné DTO a očekávané chyby z invalidRequestProvider.
     * Důvod: validace request DTO musí vynutit constraints z atributů včetně nested #[Assert\Valid].
     *
     * @param list<array{field: string, message: string}> $expectedErrors
     */
    #[DataProvider('invalidRequestProvider')]
    public function testCreateRejectsInvalidRequest(IRequestDto $request, array $expectedErrors): void
    {
        $requestValidator = self::createRequestValidator();

        try
        {
            $requestValidator->validate($request);
            self::fail('Expected InvalidContentException.');
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidContentException::class, $exception);
            self::assertSame($expectedErrors, $exception->errors());
        }
    }

    private static function createRequestValidator(): RequestValidator
    {
        return new RequestValidator(self::createSymfonyValidator());
    }

    private static function createSymfonyValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    private static function createCreateOrderRequestWithProduct(ProductDto $product): CreateOrderRequest
    {
        $request = new CreateOrderRequest();
        $request->partnerId = 'partner-1';
        $request->orderId = 'order-1';
        $request->expectedDeliveryDate = '2026-05-24T12:00:00+00:00';
        $request->products = [$product];

        return $request;
    }

    private static function createValidUpdateDeliveryDateRequest(): UpdateDeliveryDateRequest
    {
        $request = new UpdateDeliveryDateRequest();
        $request->partnerId = 'partner-1';
        $request->orderId = 'order-1';
        $request->expectedDeliveryDate = '2026-05-24T12:00:00+00:00';

        return $request;
    }

    private static function createValidProductDto(string $id = 'product-1', string $title = 'Product title', string $price = '19.99', int $quantity = 2): ProductDto
    {
        $product = new ProductDto();
        $product->id = $id;
        $product->title = $title;
        $product->price = $price;
        $product->quantity = $quantity;

        return $product;
    }
}
