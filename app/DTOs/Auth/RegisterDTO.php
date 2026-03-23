<?php

namespace App\DTOs\Auth;

use App\Exceptions\ValidationException;

class RegisterDTO
{
    public readonly string $name;
    public readonly string $email;
    public readonly string $password;

    public function __construct(array $data)
    {
        $this->validate($data);
        $this->name     = trim($data['name']);
        $this->email    = strtolower(trim($data['email']));
        $this->password = $data['password'];
    }

    private function validate(array $data): void
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est requis.';
        }

        if (empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Une adresse e-mail valide est requise.';
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
