<?php

declare(strict_types=1);

namespace App\Core\Validator;

use App\Core\Exception\InvalidContentException;
use App\Core\Values\IRequestDto;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function validate(IRequestDto $request): void
    {
        $violations = $this->validator->validate($request);

        $errors = [];

        foreach ($violations as $violation)
        {
            $field = (string) $violation->getPropertyPath();
            $errors[] = [
                'field'   => $field,
                'message' => (string) $violation->getMessage(),
            ];
        }

        if (count($errors) > 0)
        {
            throw new InvalidContentException($errors);
        }
    }
}
