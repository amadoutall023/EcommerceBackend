<?php

namespace App\Exceptions;

use RuntimeException;

class AuthException extends RuntimeException
{
    public function __construct(string $message = 'Non autorise.', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
