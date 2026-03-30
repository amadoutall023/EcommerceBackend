<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Exceptions\NotFoundException;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository
    ) {}

    public function getCatalog(array $filters = []): array
    {
        return [
            'products' => array_map(fn($p) => $p->toArray(), $this->productRepository->all($filters)),
            'categories' => array_map(fn($c) => $c->toArray(), $this->categoryRepository->all()),
        ];
    }

    public function getProductDetails(int $id): array
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new NotFoundException('Produit');
        }

        return $product->toArray();
    }

    public function getAllCategories(): array
    {
        return array_map(fn($c) => $c->toArray(), $this->categoryRepository->all());
    }

    public function createProduct(array $data): array
    {
        $data['slug'] = $this->generateUniqueProductSlug($data['name']);
        $id = $this->productRepository->create($data);
        return $this->getProductDetails($id);
    }

    public function updateProduct(int $id, array $data): array
    {
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueProductSlug($data['name'], $id);
        }

        $this->productRepository->update($id, $data);
        return $this->getProductDetails($id);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function createCategory(array $data): array
    {
        $data['slug'] = $this->generateUniqueCategorySlug($data['name']);
        $id = $this->categoryRepository->create($data);
        return $this->categoryRepository->findById($id)->toArray();
    }

    public function updateCategory(int $id, array $data): array
    {
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueCategorySlug($data['name'], $id);
        }

        $this->categoryRepository->update($id, $data);
        return $this->categoryRepository->findById($id)->toArray();
    }

    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    private function generateUniqueProductSlug(string $name, ?int $ignoreId = null): string
    {
        return $this->generateUniqueSlug(
            $name,
            fn(string $slug) => $this->productRepository->findBySlug($slug),
            $ignoreId
        );
    }

    private function generateUniqueCategorySlug(string $name, ?int $ignoreId = null): string
    {
        return $this->generateUniqueSlug(
            $name,
            fn(string $slug) => $this->categoryRepository->findBySlug($slug),
            $ignoreId
        );
    }

    private function generateUniqueSlug(string $name, callable $finder, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'item';
        $slug = $baseSlug;
        $suffix = 2;

        while (true) {
            $existing = $finder($slug);

            if (!$existing || ($ignoreId !== null && $existing->id === $ignoreId)) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
    }
}
