<?php

namespace App\Exceptions;

use RuntimeException;

class NotFoundException extends RuntimeException
{
    public function __construct(string $resource = 'Ressource', int $code = 404)
    {
        parent::__construct("{$resource} introuvable.", $code);
    }
}
