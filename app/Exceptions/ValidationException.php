<?php

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly array $errors,
        int $code = 422,
    ) {
        parent::__construct('La validation a echoue.', $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
