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
            imageUrl: self::normalizeImageUrl($data['image_url'] ?? null),
            images: isset($data['images']) ? self::normalizeImages(is_string($data['images']) ? json_decode($data['images'], true) : $data['images']) : null,
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

    private static function normalizeImages(?array $images): ?array
    {
        if ($images === null) {
            return null;
        }

        return array_map(
            fn ($image) => is_string($image) ? self::normalizeImageUrl($image) : $image,
            $images
        );
    }

    private static function normalizeImageUrl(?string $imageUrl): ?string
    {
        if (! is_string($imageUrl) || $imageUrl === '') {
            return $imageUrl;
        }

        $storagePath = parse_url($imageUrl, PHP_URL_PATH);

        if (! is_string($storagePath) || ! str_starts_with($storagePath, '/storage/')) {
            return $imageUrl;
        }

        return rtrim((string) config('app.url'), '/') . $storagePath;
    }
}
