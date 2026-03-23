<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Exceptions\NotFoundException;

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
        $id = $this->productRepository->create($data);
        return $this->getProductDetails($id);
    }

    public function updateProduct(int $id, array $data): array
    {
        $this->productRepository->update($id, $data);
        return $this->getProductDetails($id);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function createCategory(array $data): array
    {
        $id = $this->categoryRepository->create($data);
        return $this->categoryRepository->findById($id)->toArray();
    }

    public function updateCategory(int $id, array $data): array
    {
        $this->categoryRepository->update($id, $data);
        return $this->categoryRepository->findById($id)->toArray();
    }

    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }
}
