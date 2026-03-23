<?php

namespace App\Models;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $password,
        public readonly string $role,
        public readonly string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            password: $data['password'],
            role: $data['role'] ?? 'user',
            createdAt: $data['created_at'] ?? '',
        );
    }

    public function toPublicArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'role'       => $this->role,
            'created_at' => $this->createdAt,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
