<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Http;

use App\Core\Dto\Request\IRequestDto;
use App\Core\Exception\Api\InvalidContentException;
use App\Core\Http\RequestDtoFactory;
use App\Core\Validator\RequestValidator;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use InvalidArgumentException;
use Iterator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class RequestDtoFactoryTest extends TestCase
{
    /** @return Iterator<string, array{content: string, expectedErrors: list<array{field: string, message: string}>}> */
    public static function emptyBodyProvider(): Iterator
    {
        yield 'empty string' => [
            'content'        => '',
            'expectedErrors' => [
                ['field' => '', 'message' => 'Request body must not be empty.'],
            ],
        ];

        yield 'whitespace only' => [
            'content'        => '   ',
            'expectedErrors' => [
                ['field' => '', 'message' => 'Request body must not be empty.'],
            ],
        ];

        yield 'tab and newline' => [
            'content'        => "\t\n",
            'expectedErrors' => [
                ['field' => '', 'message' => 'Request body must not be empty.'],
            ],
        ];
    }

    /**
     * Ověřuje, že RequestDtoFactory::create() odmítne prázdné tělo požadavku.
     * Vstup: řetězce z emptyBodyProvider.
     * Důvod: API požadavky musí obsahovat JSON payload před deserializací a validací.
     *
     * @param list<array{field: string, message: string}> $expectedErrors
     */
    #[DataProvider('emptyBodyProvider')]
    public function testCreateRejectsEmptyBody(string $content, array $expectedErrors): void
    {
        $factory = self::createFactory(
            self::createStub(SerializerInterface::class),
            self::createPassingValidator(),
        );

        try
        {
            $factory->create($content, CreateOrderRequest::class);
            self::fail(sprintf('Expected InvalidContentException for content "%s".', $content));
        }
        catch (InvalidContentException $exception)
        {
            self::assertSame($expectedErrors, $exception->errors());
        }
        catch (ExceptionInterface)
        {
            self::fail('Unexpected serializer exception.');
        }
    }

    /** @return Iterator<string, array{content: string, exception: Throwable}> */
    public static function invalidJsonProvider(): Iterator
    {
        yield 'not encodable value exception' => [
            'content'   => '{invalid',
            'exception' => new NotEncodableValueException('Invalid JSON.'),
        ];

        yield 'json exception' => [
            'content'   => '{"broken":}',
            'exception' => new JsonException('Syntax error'),
        ];
    }

    /**
     * Ověřuje, že RequestDtoFactory::create() odmítne neplatné JSON payloady.
     * Vstup: content a výjimka ze serializeru z invalidJsonProvider.
     * Důvod: poškozený JSON musí být mapován na strukturovanou 400 InvalidContentException odpověď.
     *
     * @throws ExceptionInterface
     */
    #[DataProvider('invalidJsonProvider')]
    public function testCreateRejectsInvalidJson(string $content, Throwable $exception): void
    {
        $factory = self::createFactory(self::createSerializerThrowing($exception), self::createPassingValidator());

        try
        {
            $factory->create($content, CreateOrderRequest::class);
            self::fail(sprintf('Expected InvalidContentException for content "%s".', $content));
        }
        catch (InvalidContentException $invalidContentException)
        {
            self::assertSame([
                ['field' => '', 'message' => 'Invalid JSON payload.'],
            ], $invalidContentException->errors());
        }
    }

    /** @return Iterator<string, array{content: string, exception: Throwable}> */
    public static function invalidFormatProvider(): Iterator
    {
        yield 'not normalizable value exception' => [
            'content'   => '{"partnerId":123}',
            'exception' => new NotNormalizableValueException('Expected string.'),
        ];

        yield 'partial denormalization exception' => [
            'content'   => '{"partnerId":"p1","products":"invalid"}',
            'exception' => new PartialDenormalizationException([], []),
        ];
    }

    /**
     * Ověřuje, že RequestDtoFactory::create() odmítne neplatný formát nebo datové typy.
     * Vstup: content a výjimka ze serializeru z invalidFormatProvider.
     * Důvod: nesoulad typů nebo částečná denormalizace musí vrátit srozumitelnou chybu klientovi.
     *
     * @throws ExceptionInterface
     */
    #[DataProvider('invalidFormatProvider')]
    public function testCreateRejectsInvalidRequestFormat(string $content, Throwable $exception): void
    {
        $factory = self::createFactory(self::createSerializerThrowing($exception), self::createPassingValidator());

        try
        {
            $factory->create($content, CreateOrderRequest::class);
            self::fail(sprintf('Expected InvalidContentException for content "%s".', $content));
        }
        catch (InvalidContentException $invalidContentException)
        {
            self::assertSame([
                ['field' => '', 'message' => 'Invalid request format or data types.'],
            ], $invalidContentException->errors());
        }
    }

    /** @return Iterator<string, array{violations: list<array{field: string, message: string}>, expectedErrors: list<array{field: string, message: string}>}> */
    public static function validationFailureProvider(): Iterator
    {
        yield 'single field violation smoke test' => [
            'violations' => [
                ['field' => 'partnerId', 'message' => 'Partner ID must not be blank.'],
            ],
            'expectedErrors' => [
                ['field' => 'partnerId', 'message' => 'Partner ID must not be blank.'],
            ],
        ];
    }

    /**
     * Ověřuje, že RequestDtoFactory::create() propaguje chyby validace z RequestValidator.
     *
     * Vstup: smoke case z validationFailureProvider.
     * Důvod: factory musí delegovat validaci DTO na RequestValidator; detailní mapování violations testuje RequestValidatorTest.
     *
     * @param list<array{field: string, message: string}> $violations
     * @param list<array{field: string, message: string}> $expectedErrors
     *
     * @throws ExceptionInterface
     */
    #[DataProvider('validationFailureProvider')]
    public function testCreateRejectsValidationFailures(array $violations, array $expectedErrors): void
    {
        $dto = new CreateOrderRequest();

        $factory = self::createFactory(
            self::createSerializerReturning($dto),
            self::createValidatorReturning(self::createViolationList($violations)),
        );

        try
        {
            $factory->create('{"partnerId":""}', CreateOrderRequest::class);
            self::fail('Expected InvalidContentException.');
        }
        catch (InvalidContentException $exception)
        {
            self::assertSame($expectedErrors, $exception->errors());
        }
    }

    /** @return Iterator<string, array{class: class-string<IRequestDto>, content: string}> */
    public static function validRequestProvider(): Iterator
    {
        yield 'create order request' => [
            'class'   => CreateOrderRequest::class,
            'content' => '{"partnerId":"p1"}',
        ];

        yield 'update delivery date request' => [
            'class'   => UpdateDeliveryDateRequest::class,
            'content' => '{"orderId":"o1"}',
        ];
    }

    /**
     * Ověřuje, že RequestDtoFactory::create() deserializuje, validuje a vrátí DTO.
     * Vstup: třída DTO a JSON content z validRequestProvider.
     * Důvod: úspěšné požadavky musí projít deserializací a validací před dosažením handlerů.
     *
     * @throws ExceptionInterface
     * @throws InvalidContentException
     * @throws MockException
     */
    #[DataProvider('validRequestProvider')]
    public function testHappyFlowCreateValidatesAndReturnsDto(string $class, string $content): void
    {
        $dto = match ($class)
        {
            CreateOrderRequest::class        => new CreateOrderRequest(),
            UpdateDeliveryDateRequest::class => new UpdateDeliveryDateRequest(),
            default                          => throw new InvalidArgumentException(sprintf('Unsupported class "%s".', $class)),
        };

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('deserialize')
            ->with($content, $class, 'json')
            ->willReturn($dto);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $factory = self::createFactory($serializer, new RequestValidator($validator));
        $result = $factory->create($content, $class);

        self::assertSame($dto, $result);
    }

    /**
     * Ověřuje, že tělo obsahující pouze NBSP neprojde empty check a pokračuje k deserializaci.
     * Vstup: řetězec s jediným non-breaking space znakem.
     * Důvod: PHP trim() NBSP neodstraňuje — factory musí chování konzistentně delegovat na serializer.
     *
     * @throws ExceptionInterface
     * @throws InvalidContentException
     * @throws MockException
     */
    public function testHappyFlowNonEmptyNbspBodyProceedsToDeserialize(): void
    {
        $content = "\u{00A0}";
        $dto = new CreateOrderRequest();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('deserialize')
            ->with($content, CreateOrderRequest::class, 'json')
            ->willReturn($dto);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $factory = self::createFactory($serializer, new RequestValidator($validator));
        $result = $factory->create($content, CreateOrderRequest::class);

        self::assertSame($dto, $result);
    }

    private static function createFactory(SerializerInterface $serializer, RequestValidator $validator): RequestDtoFactory
    {
        return new RequestDtoFactory($serializer, $validator);
    }

    private static function createPassingValidator(): RequestValidator
    {
        $validator = self::createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        return new RequestValidator($validator);
    }

    private static function createValidatorReturning(ConstraintViolationList $violations): RequestValidator
    {
        $validator = self::createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violations);

        return new RequestValidator($validator);
    }

    private static function createSerializerReturning(IRequestDto $dto): SerializerInterface
    {
        $serializer = self::createStub(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn($dto);

        return $serializer;
    }

    private static function createSerializerThrowing(Throwable $exception): SerializerInterface
    {
        $serializer = self::createStub(SerializerInterface::class);
        $serializer->method('deserialize')->willThrowException($exception);

        return $serializer;
    }

    /**
     * @param list<array{field: string, message: string}> $items
     */
    private static function createViolationList(array $items): ConstraintViolationList
    {
        $violations = [];

        foreach ($items as $item)
        {
            $violations[] = new ConstraintViolation(
                $item['message'],
                '',
                [],
                null,
                $item['field'],
                null,
            );
        }

        return new ConstraintViolationList($violations);
    }
}
