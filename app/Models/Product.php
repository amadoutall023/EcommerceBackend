<?php

namespace App\Models;

class Product
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly float $price,
        public readonly ?float $originalPrice,
        public readonly int $stock,
        public readonly ?string $imageUrl,
        public readonly ?array $images,
        public readonly ?array $sizes,
        public readonly ?array $colors,
        public readonly string $createdAt,
        public readonly ?string $categoryName = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            categoryId: (int) $data['category_id'],
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            price: (float) $data['price'],
            originalPrice: isset($data['original_price']) ? (float) $data['original_price'] : null,
            stock: (int) $data['stock'],
            imageUrl: $data['image_url'] ?? null,
            images: isset($data['images']) ? (is_string($data['images']) ? json_decode($data['images'], true) : $data['images']) : null,
            sizes: isset($data['sizes']) ? (is_string($data['sizes']) ? json_decode($data['sizes'], true) : $data['sizes']) : null,
            colors: isset($data['colors']) ? (is_string($data['colors']) ? json_decode($data['colors'], true) : $data['colors']) : null,
            createdAt: $data['created_at'] ?? '',
            categoryName: $data['category_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'category_id'    => $this->categoryId,
            'category_name'  => $this->categoryName,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => $this->price,
            'original_price' => $this->originalPrice,
            'stock'          => $this->stock,
            'image_url'      => $this->imageUrl,
            'images'         => $this->images,
            'sizes'          => $this->sizes,
            'colors'         => $this->colors,
            'created_at'     => $this->createdAt,
        ];
    }

    public function isInStock(int $qty = 1): bool
    {
        return $this->stock >= $qty;
    }
}
