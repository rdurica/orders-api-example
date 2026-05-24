<?php

declare(strict_types=1);

namespace App\Core\Validator;

use App\Core\Dto\Request\IRequestDto;
use App\Core\Exception\Api\InvalidContentException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @throws InvalidContentException
     */
    public function validate(IRequestDto $request): void
    {
        $errors = $this->collectErrors($this->validator->validate($request));

        if ($errors !== [])
        {
            throw new InvalidContentException($errors);
        }
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    private function collectErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation)
        {
            $errors[] = [
                'field'   => (string) $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $errors;
    }
}
