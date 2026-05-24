<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Listener;

use App\Core\Enum\ApiErrorCode;
use App\Core\Exception\Api\ApiException;
use App\Core\Listener\ApiExceptionListener;
use App\Orders\Exception\Api\OrderAlreadyExistsException;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

final class ApiExceptionListenerTest extends TestCase
{
    /**
     * Ověřuje, že ApiExceptionListener vrátí RFC 7807 problem+json s detaily v debug režimu.
     * Vstup: OrderAlreadyExistsException, listener vytvořený s debug=true.
     * Důvod: debug režim musí vystavit plnou zprávu výjimky pro usnadnění lokálního vývoje.
     *
     * @throws JsonException
     */
    public function testApiExceptionReturnsProblemJsonWithDetailsInDebugMode(): void
    {
        $listener = new ApiExceptionListener(true);
        $event = $this->createExceptionEvent(new OrderAlreadyExistsException());

        ($listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $payload = json_decode($response->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('order_already_exists', $payload['code']);
        self::assertSame(409, $payload['status']);
        self::assertSame('Order with this number already exists.', $payload['detail']);
    }

    /**
     * Ověřuje, že ApiExceptionListener skryje detaily výjimky v produkčním režimu.
     * Vstup: OrderAlreadyExistsException, listener vytvořený s debug=false.
     * Důvod: produkční odpovědi nesmí unikat interní chybové zprávy ani chyby polí.
     *
     * @throws JsonException
     */
    public function testApiExceptionHidesDetailsInProductionMode(): void
    {
        $listener = new ApiExceptionListener(false);
        $event = $this->createExceptionEvent(new OrderAlreadyExistsException());

        ($listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);

        $payload = json_decode($response->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Conflict', $payload['detail']);
        self::assertSame([], $payload['errors']);
    }

    /**
     * Ověřuje, že ApiExceptionListener mapuje neošetřené výjimky na HTTP 500 problem+json.
     * Vstup: RuntimeException se zprávou "Something broke.", debug=false.
     * Důvod: neočekávané chyby musí vrátit obecnou 500 odpověď bez vystavení interních detailů.
     *
     * @throws JsonException
     */
    public function testGenericExceptionReturnsInternalServerError(): void
    {
        $listener = new ApiExceptionListener(false);
        $event = $this->createExceptionEvent(new RuntimeException('Something broke.'));

        ($listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $payload = json_decode($response->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Critical', $payload['code']);
        self::assertSame('An unexpected error.', $payload['detail']);
    }

    /**
     * Ověřuje, že ApiExceptionListener mapuje JsonException na HTTP 400 bad request.
     * Vstup: JsonException, debug=false.
     * Důvod: neplatný JSON během zpracování požadavku musí vrátit klientskou 400 odpověď.
     *
     * @throws JsonException
     */
    public function testJsonExceptionReturnsBadRequest(): void
    {
        $listener = new ApiExceptionListener(false);
        $event = $this->createExceptionEvent(new JsonException('Invalid JSON.'));

        ($listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $payload = json_decode($response->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Bad Request', $payload['code']);
        self::assertSame('Invalid JSON payload.', $payload['detail']);
    }

    /**
     * Ověřuje, že ApiExceptionListener zahrnuje kód chyby a chyby polí do problem type.
     * Vstup: vlastní ApiException s kódem INVALID_DATA a jednou chybou pole, debug=true.
     * Důvod: API výjimky musí produkovat RFC 7807 payloady s type URL, title a strukturovanými chybami.
     *
     * @throws JsonException
     */
    public function testCustomApiExceptionIncludesErrorCodeInProblemType(): void
    {
        $exception = new class extends ApiException
        {
            public function __construct()
            {
                parent::__construct(ApiErrorCode::INVALID_DATA, 'Invalid data.', 422);
                $this->addError('field', 'Invalid value.');
            }
        };

        $listener = new ApiExceptionListener(true);
        $event = $this->createExceptionEvent($exception);

        ($listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);

        $payload = json_decode($response->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('https://orders-api.example/errors/invalid_data', $payload['type']);
        self::assertSame('Invalid data', $payload['title']);
        self::assertSame([
            ['field' => 'field', 'message' => 'Invalid value.'],
        ], $payload['errors']);
    }

    private function createExceptionEvent(Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            self::createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
