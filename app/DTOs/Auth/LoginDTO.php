<?php

namespace App\DTOs\Auth;

use App\Exceptions\ValidationException;

class LoginDTO
{
    public readonly string $email;
    public readonly string $password;

    public function __construct(array $data)
    {
        $this->validate($data);
        $this->email    = strtolower(trim($data['email']));
        $this->password = $data['password'];
    }

    private function validate(array $data): void
    {
        $errors = [];

        if (empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Une adresse e-mail valide est requise.';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        }

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
