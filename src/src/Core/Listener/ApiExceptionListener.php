<?php

declare(strict_types=1);

namespace App\Core\Listener;

use App\Core\Exception\Api\ApiException;
use BackedEnum;
use DomainException;
use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use UnitEnum;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final readonly class ApiExceptionListener
{
    public function __construct(#[Autowire('%kernel.debug%')] private bool $debug)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $exposeDetails = $this->debug;

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $type = 'Critical';
        $title = Response::$statusTexts[$status] ?? 'Unknown error';
        $detail = 'An unexpected error.';
        $headers = [];
        $trace = null;
        $errors = [];

        if ($e instanceof HttpExceptionInterface)
        {
            $status = $e->getStatusCode();
            $headers = $e->getHeaders();
            $type = Response::$statusTexts[$status] ?? $title;
        }

        if ($e instanceof JsonException)
        {
            $status = Response::HTTP_BAD_REQUEST;
            $type = 'Bad Request';
            $detail = 'Invalid JSON payload.';
        }

        if (
            $e instanceof NotEncodableValueException
            || $e instanceof NotNormalizableValueException
            || $e instanceof PartialDenormalizationException
        ) {
            $status = Response::HTTP_BAD_REQUEST;
            $type = 'Bad Request';
            $detail = 'Invalid request format or data types.';
        }

        if ($e instanceof DomainException)
        {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $type = 'unexpected';
            $detail = 'An unexpected error.';
            $trace = null;
        }

        if ($exposeDetails && !($e instanceof ApiException) && !($e instanceof DomainException))
        {
            if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR)
            {
                $detail = $e->getMessage();
                $trace = $e->getTrace();
            }
        }

        $data = [];

        if ($e instanceof ApiException)
        {
            $type = $e->errorCode()->value;
            $status = $e->getCode();
            $detail = $exposeDetails
                ? $e->getMessage()
                : (Response::$statusTexts[$status] ?? 'Request failed.');
            $trace = null;
            $errors = $exposeDetails ? $e->errors() : [];
            $data = $e->data();
        }

        $problem = [
            'type'   => sprintf('https://orders-api.example/errors/%s', $type),
            'code'   => $type,
            'title'  => self::normalizeErrorTitle($type),
            'status' => $status,
            'detail' => $detail,
            'errors' => $errors,
            ...$data,
        ];

        if ($trace)
        {
            $problem['trace'] = $trace;
        }

        $response = new JsonResponse(
            $this->normalizeData($problem),
            $status,
            array_merge(['Content-Type' => 'application/problem+json'], $headers),
        );

        $event->setResponse($response);
    }

    private static function normalizeErrorTitle(string $type): string
    {
        $type = basename($type);
        $title = str_replace('_', ' ', $type);

        return ucfirst(strtolower($title));
    }

    private function normalizeData(mixed $data): mixed
    {
        if ($data instanceof BackedEnum)
        {
            return $data->value;
        }

        if ($data instanceof UnitEnum)
        {
            return $data->name;
        }

        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $data[$key] = $this->normalizeData($value);
            }
        }

        if (is_object($data) && method_exists($data, '__toString'))
        {
            return (string) $data;
        }

        return $data;
    }
}
