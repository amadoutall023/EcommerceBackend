<?php

namespace App\Models;

class Category
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $imageUrl,
        public readonly string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            slug: $data['slug'],
            imageUrl: $data['image_url'] ?? null,
            createdAt: $data['created_at'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'image_url'  => $this->imageUrl,
            'created_at' => $this->createdAt,
        ];
    }
}
