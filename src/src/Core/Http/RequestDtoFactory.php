<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Dto\Request\IRequestDto;
use App\Core\Exception\Api\InvalidContentException;
use App\Core\Validator\RequestValidator;
use JsonException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\SerializerInterface;

final class RequestDtoFactory
{
    private const string FORMAT = 'json';

    public function __construct(private readonly SerializerInterface $serializer, private readonly RequestValidator $validator)
    {
    }

    /**
     * @param class-string<T> $class
     *
     * @return T
     * @template T of IRequestDto
     * @throws InvalidContentException
     * @throws ExceptionInterface
     */
    public function create(string $content, string $class): IRequestDto
    {
        if (trim($content) === '')
        {
            throw new InvalidContentException([['field' => '', 'message' => 'Request body must not be empty.']]);
        }

        try
        {
            /** @var T $dto */
            $dto = $this->serializer->deserialize($content, $class, self::FORMAT);
        }
        catch (JsonException|NotEncodableValueException)
        {
            throw new InvalidContentException([['field' => '', 'message' => 'Invalid JSON payload.',],]);
        }
        catch (NotNormalizableValueException|PartialDenormalizationException)
        {
            throw new InvalidContentException([['field' => '', 'message' => 'Invalid request format or data types.',],]);
        }

        $this->validator->validate($dto);

        return $dto;
    }
}
